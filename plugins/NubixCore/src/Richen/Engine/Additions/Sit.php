<?php

namespace Richen\Engine\Additions;

use pocketmine\block\BlockIds;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\SetEntityLinkPacket;
use pocketmine\Player;
use pocketmine\Server;

class Sit extends \Richen\Engine\Manager {
	public $sittingPlayers = [];

	public function sendSittingPackets(Player $viewer, Player $rider, int $eid) {
		$pk = new AddEntityPacket();
		$pk->eid = $eid;
		$pk->type = 84;
		$pk->x = $rider->getFloorX() + 0.5;
		$pk->y = $rider->y - ($rider->getScale() >= 1.0 ? 0.12 : -11.65);
		$pk->z = $rider->getFloorZ() + 0.5;
		$pk->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, (1 << Entity::DATA_FLAG_IMMOBILE) | (1 << Entity::DATA_FLAG_INVISIBLE)]
		];
		$pk->speedX = $pk->speedY = $pk->speedZ = $pk->yaw = $pk->pitch = 0;

		$viewer->dataPacket($pk);

		$pk2 = new SetEntityLinkPacket();
		$pk2->from = $pk->eid;
		$pk2->to = $rider->getId();
		$pk2->type = 1;

		$viewer->dataPacket($pk2);
	}

	public function sitHere(Player $player) {
		$packets2batch = [];

		$pk = new AddEntityPacket();
		$pk->eid = Entity::$entityCount++;
		$pk->type = 84;
		$pk->x = $player->getFloorX() + 0.5;
		$pk->y = $player->getFloorY() - ($player->getScale() >= 1.0 ? 0.75 : 0.4);
		$pk->z = $player->getFloorZ() + 0.5;
		$pk->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, (1 << Entity::DATA_FLAG_IMMOBILE) | (1 << Entity::DATA_FLAG_INVISIBLE)]
		];
		$pk->speedX = $pk->speedY = $pk->speedZ = $pk->yaw = $pk->pitch = 0;

		$packets2batch[] = $pk;

		$player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_RIDING, true);
		$player->setDataProperty(Entity::DATA_RIDER_SEAT_POSITION, Entity::DATA_TYPE_VECTOR3F, [0.0, 1.5, 0.0]);

		$pk2 = new SetEntityLinkPacket();
		$pk2->from = $pk->eid;
		$pk2->to = $player->getId();
		$pk2->type = 1;

		$packets2batch[] = $pk2;

		Server::getInstance()->batchPackets(Server::getInstance()->getOnlinePlayers(), $packets2batch);

		$this->sittingPlayers[$player->getName()] = $pk->eid;

        $this->serv()->getScheduler()->scheduleDelayedTask(new class($player) extends \pocketmine\scheduler\Task {
            private $player; public function __construct(Player $player) { $this->player = $player; }
            public function onRun($currentTick) : void { $this->player->addTitle('', '§eВы присели', 10, 20, 10); }
        }, 20);
	}

	public function standUp(Player $player) {
		if (!isset($this->sittingPlayers[$playerName = $player->getName()])) {
			return;
		}

		$packets2batch = [];

		$pk = new RemoveEntityPacket();
		$pk->eid = $this->sittingPlayers[$playerName];

		$packets2batch[] = $pk;

		$pk2 = new SetEntityLinkPacket();
		$pk2->from = $pk->eid;
		$pk2->to = $player->getId();
		$pk2->type = 0;

		$packets2batch[] = $pk2;

		$player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_RIDING, false);

		Server::getInstance()->batchPackets(Server::getInstance()->getOnlinePlayers(), $packets2batch);

		unset($this->sittingPlayers[$playerName]);
	}

	public function startSleeping(Player $player) {
		$pos = $player->floor();
		$b = $player->level->getBlock($pos);

		$ev = new PlayerBedEnterEvent($player, $b);
        Server::getInstance()->getPluginManager()->callEvent($ev);
		if ($ev->isCancelled()) {
			return false;
		}

		(function() use ($pos){ $this->{"sleeping"} = $pos; })->call($player);

		$player->setDataProperty(Player::DATA_PLAYER_BED_POSITION, Entity::DATA_TYPE_POS, [$pos->x, $pos->y, $pos->z]);
		$player->setDataFlag(Player::DATA_PLAYER_FLAGS, Player::DATA_PLAYER_FLAG_SLEEP, true, Entity::DATA_TYPE_BYTE);

		$player->level->sleepTicks = 60;

		return true;
	}

	public function onCommand(CommandSender $player, Command $command, $label, array $args){
		if(!$player instanceof Player){
			$player->sendMessage("§7► §cКоманду можно использовать только в игре!");
			return true;
		}
		if($command->getName() === "seat"){
			if(!isset($this->sittingPlayers[$player->getName()])){
				$this->sitHere($player);
				if($player->getLevel()->getBlock($player)->getSide(Vector3::SIDE_DOWN)->getId() !== BlockIds::AIR){
					$player->sendMessage("§6► §eТы успешно сел(-а) на грязный пол!");
				}else{
					$player->sendMessage("§6► §eТы успешно сел(-а) на невидимое кресло!");
				}
			}else{
				$this->standUp($player);
				$player->sendMessage("§6► §eТы успешно встал(-а).");
			}
		}elseif($command->getName() === "sleep"){
			if(isset($this->sittingPlayers[$player->getName()])){
				$this->standUp($player);
			}
			if((function() : bool{
				return !$this->{"sleeping"} instanceof Vector3;
			})->call($player)){
				$this->startSleeping($player);
				$player->sendMessage("§6► §eТы успешно лёг(-ла) спать.");
			}else{
				$player->stopSleep();
				$player->sendMessage("§6► §eТы успешно проснулся(-ась).");
			}
		}elseif($command->getName() === "small"){
			if($player->getScale() < 1.0){
				$player->sendMessage("§7► §cТы уже маленького роста! Получить порцию растишки: §b/normal");
				return true;
			}
			$player->setScale(0.5);
			$player->sendMessage("§6► §eТы успешно уменьшился(-ась).");
			if(isset($this->sittingPlayers[$player->getName()])){
				$this->standUp($player);
			}
		}elseif($command->getName() === "normal"){
			if($player->getScale() === 1.0){
				$player->sendMessage("§7► §cТы и так нормального роста!");
				return true;
			}
			$player->setScale(1.0);
			$player->sendMessage("§6► §eТеперь твой рост нормальный.");
			if(isset($this->sittingPlayers[$player->getName()])){
				$this->standUp($player);
			}
		}
		return true;
	}
}