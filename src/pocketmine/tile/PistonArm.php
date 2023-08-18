<?php

declare(strict_types = 1);

namespace pocketmine\tile;

use pocketmine\nbt\tag\{ByteTag, CompoundTag, FloatTag, IntTag, StringTag};

class PistonArm extends Spawnable {
	
	public function getSpawnCompound(){
		return new CompoundTag("", [
			new StringTag("id", Tile::PISTON_ARM),
			new IntTag("x", (int)$this->x),
			new IntTag("y", (int)$this->y),
			new IntTag("z", (int)$this->z),
			new FloatTag("Progress", $this->namedtag['Progress']),
			new ByteTag("State", $this->namedtag['State']),
		]);
	}
}