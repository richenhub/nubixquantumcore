<?php

declare(strict_types = 1);

namespace pocketmine\level\generator\object;

use pocketmine\block\Block;
use pocketmine\block\Leaves;
use pocketmine\block\Wood;
use pocketmine\level\loadchunk\ChunkManager;
use pocketmine\utils\Random;

class OakTree extends Tree {

	/**
	 * OakTree constructor.
	 */
public function __construct(){
		$this->trunkBlock = Block::LOG;
		$this->leafBlock = Block::LEAVES;
		$this->leafType = Leaves::OAK;
		$this->type = Wood::OAK;
}

	/**
	 * @param ChunkManager $level
	 * @param              $x
	 * @param              $y
	 * @param              $z
	 * @param Random       $random
	 */
public function placeObject(ChunkManager $level, $x, $y, $z, Random $random){
		$this->treeHeight = $random->nextBoundedInt(3) + 4;
		parent::placeObject($level, $x, $y, $z, $random);
}
}