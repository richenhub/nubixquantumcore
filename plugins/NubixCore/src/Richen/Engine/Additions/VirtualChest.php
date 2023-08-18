<?php

declare(strict_types = 1);

namespace Richen\Engine\Additions;

class VirtualChest extends \pocketmine\tile\Chest {
	private $viewerName;
	protected $doubleInventory;
	private $shouldBeSpawned = false;
	public function __construct(\pocketmine\level\Level $level, \pocketmine\nbt\tag\CompoundTag $nbt, string $viewerName) { parent::__construct($level, $nbt); $this->viewerName = $viewerName; }
	public function getInventory() { return $this->doubleInventory instanceof PersonalDoubleInventory ? $this->doubleInventory : $this->inventory; }
	public function setDoubleInventory(PersonalDoubleInventory $inventory) { $this->doubleInventory = $inventory; }
	public function setShouldBeSpawned() { $this->shouldBeSpawned = true; }
	public function spawnTo(\pocketmine\Player $player) { if (!$this->shouldBeSpawned or $this->viewerName !== $player->getName()) { return false; } return parent::spawnTo($player); }
	public function saveNBT() { $this->namedtag = new \pocketmine\nbt\tag\ByteTag('', 0); }
}