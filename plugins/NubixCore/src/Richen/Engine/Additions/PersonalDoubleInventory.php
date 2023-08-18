<?php

declare(strict_types = 1);

namespace Richen\Engine\Additions;

class PersonalDoubleInventory extends \pocketmine\inventory\DoubleChestInventory {
	private $viewerName;
	public function __construct(VirtualChest $left, VirtualChest $right, string $viewerName) { parent::__construct($left, $right); $this->viewerName = $viewerName; }
	public function onOpen(\pocketmine\Player $who) { \pocketmine\inventory\ContainerInventory::onOpen($who); }
	public function onClose(\pocketmine\Player $who) { \pocketmine\inventory\ContainerInventory::onClose($who); \Richen\Engine\Auction\Auction::getInstance()->addToDelayedClose($who); }
	public function getContents($withAir = false): array { return []; }
	public function firstOccupied() { return -1; }
	public function getViewerName(): string { return $this->viewerName; }
}
