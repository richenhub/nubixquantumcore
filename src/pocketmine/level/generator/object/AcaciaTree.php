<?php

namespace pocketmine\level\generator\object;

use pocketmine\block\Block;
use pocketmine\block\Leaves2;
use pocketmine\block\Wood2;

class AcaciaTree extends Tree {
	/**
	 * AcaciaTree constructor.
	 */
	public function __construct(){
		$this->trunkBlock = Block::WOOD2;
		$this->leafBlock = Block::LEAVES2;
		$this->leafType = Leaves2::ACACIA;
		$this->type = Wood2::ACACIA;
		$this->treeHeight = 8;
	}
}