<?php

namespace pocketmine\tile;

use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\Player;

abstract class Spawnable extends Tile{
	/** @var string|null */
	private $spawnCompoundCache = null;
	/** @var NBT|null */
	private static $nbtWriter = null;

	public function createSpawnPacket() : BlockEntityDataPacket{
		$pk = new BlockEntityDataPacket();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->namedtag = $this->getSerializedSpawnCompound();

		return $pk;
	}

	public function spawnTo(Player $player){
		if($this->closed){
			return false;
		}

		$player->dataPacket($this->createSpawnPacket());

		return true;
	}

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->spawnToAll();
	}

	public function spawnToAll(){
		if($this->closed){
			return;
		}

		$this->level->broadcastPacketToViewers($this, $this->createSpawnPacket());
	}

	protected function onChanged(){
		$this->spawnCompoundCache = null;
		$this->spawnToAll();

		$this->level->clearChunkCache($this->getFloorX() >> 4, $this->getFloorZ() >> 4);
	}

	final public function getSerializedSpawnCompound() : string{
		if($this->spawnCompoundCache === null){
			if(self::$nbtWriter === null){
				self::$nbtWriter = new NBT(NBT::LITTLE_ENDIAN);
			}

			self::$nbtWriter->setData($this->getSpawnCompound());
			$this->spawnCompoundCache = self::$nbtWriter->write(true);
		}

		return $this->spawnCompoundCache;
	}

	public abstract function getSpawnCompound();

	public function updateCompoundTag(CompoundTag $nbt, Player $player) : bool{
		return false;
	}
}
