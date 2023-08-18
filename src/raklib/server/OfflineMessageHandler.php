<?php
/*
╭━━━┳╮╱╱╱╱╱╱╱╱╱╭━━━╮
┃╭━╮┃┃╱╱╱╱╱╱╱╱╱┃╭━╮┃
┃╰━━┫┃╭━━┳━━┳━━┫╰━╯┣━┳━━╮
╰━━╮┃┃┃┃━┫┃━┫╭╮┃╭━━┫╭┫╭╮┃
┃╰━╯┃╰┫┃━┫┃━┫╰╯┃┃╱╱┃┃┃╰╯┃
╰━━━┻━┻━━┻━━┫╭━┻╯╱╱╰╯╰━━╯
╱╱╱╱╱╱╱╱╱╱╱╱┃┃
╱╱╱╱╱╱╱╱╱╱╱╱╰╯
*/

declare(strict_types=1);

namespace raklib\server;

use raklib\protocol\{IncompatibleProtocolVersion, OfflineMessage, OpenConnectionReply1, OpenConnectionReply2, OpenConnectionRequest1, OpenConnectionRequest2, UnconnectedPing, UnconnectedPong};
use function min;

class OfflineMessageHandler{
	/** @var SessionManager */
	private $sessionManager;

	public function __construct(SessionManager $manager){
		$this->sessionManager = $manager;
	}

	public function handle(OfflineMessage $packet, string $source, int $port){
		switch($packet::$ID){
			case UnconnectedPing::$ID:
				/** @var UnconnectedPing $packet */
				$pk = new UnconnectedPong();
				$pk->serverID = $this->sessionManager->getID();
				$pk->pingID = $packet->pingID;
				$pk->serverName = $this->sessionManager->getName();
				$this->sessionManager->sendPacket($pk, $source, $port);
				return true;
			case OpenConnectionRequest1::$ID:
				/** @var OpenConnectionRequest1 $packet */
				$serverProtocol = $this->sessionManager->getProtocolVersion();
				if($packet->protocol !== $serverProtocol){
					$pk = new IncompatibleProtocolVersion();
					$pk->protocolVersion = $serverProtocol;
					$pk->serverId = $this->sessionManager->getID();
					$this->sessionManager->sendPacket($pk, $source, $port);
					$this->sessionManager->getLogger()->notice("Refused connection from $source $port due to incompatible RakNet protocol version (expected $serverProtocol, got $packet->protocol)");
				}else{
					$pk = new OpenConnectionReply1();
					$pk->mtuSize = $packet->mtuSize + 28;
					$pk->serverID = $this->sessionManager->getID();
					$this->sessionManager->sendPacket($pk, $source, $port);
				}
				return true;
			case OpenConnectionRequest2::$ID:
				/** @var OpenConnectionRequest2 $packet */

				if($packet->serverPort === $this->sessionManager->getPort() or !$this->sessionManager->portChecking){
					if($packet->mtuSize < Session::MIN_MTU_SIZE){
						$this->sessionManager->getLogger()->debug("Not creating session for $source $port due to bad MTU size $packet->mtuSize");
						return true;
					}
					$mtuSize = min($packet->mtuSize, $this->sessionManager->getMaxMtuSize());
					$pk = new OpenConnectionReply2();
					$pk->mtuSize = $mtuSize;
					$pk->serverID = $this->sessionManager->getID();
					$pk->clientAddress = $source;
					$pk->clientPort = $port;
					$this->sessionManager->sendPacket($pk, $source, $port);
					$this->sessionManager->createSession($source, $port, $packet->clientID, $mtuSize);
				}else{
					$this->sessionManager->getLogger()->debug("Not creating session for $source $port due to mismatched port, expected " . $this->sessionManager->getPort() . ", got " . $packet->serverPort);
				}

				return true;
		}

		return false;
	}

}