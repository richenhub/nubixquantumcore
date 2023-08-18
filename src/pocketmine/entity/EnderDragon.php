<?php

namespace pocketmine\entity;

use pocketmine\Player;
use pocketmine\network\mcpe\protocol\AddEntityPacket;

class EnderDragon extends Monster {

	const NETWORK_ID = 53;

	public $dropExp = [500, 12, 000];

	public function initEntity(){
		$this->setMaxHealth(200);
		parent::initEntity();
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Ender Dragon";
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = self::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);

		parent::spawnTo($player);
	}

}