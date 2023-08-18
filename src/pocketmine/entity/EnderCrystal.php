<?php

namespace pocketmine\entity;

use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;

class EnderCrystal extends Vehicle{
	
   const NETWORK_ID = 71;

   public $height = 0.7;
   public $width = 1.6;
   public $gravity = 0.5;
   public $drag = 0.1;

   public function __construct(Level $level, CompoundTag $nbt){
    parent::__construct($level, $nbt);
}

public function spawnTo(Player $p){
    $packet = new AddEntityPacket();
	$packet->eid = $this->getId();
	$packet->type = EnderCrystal::NETWORK_ID;
	$packet->x = $this->x;
	$packet->y = $this->y;
	$packet->z = $this->z;
	$packet->speedX = 0;
	$packet->speedY = 0;
	$packet->speedZ = 0;
	$packet->yaw = 0;
	$packet->pitch = 0;
	$packet->metadata = $this->dataProperties;
	$p->dataPacket($packet);
	parent::spawnTo($p);
}
}