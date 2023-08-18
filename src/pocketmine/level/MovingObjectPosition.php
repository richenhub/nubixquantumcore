<?php

declare(strict_types = 1);

namespace pocketmine\level;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

class MovingObjectPosition {

	/** 0 = block, 1 = entity */
	public $typeOfHit;

	public $blockX;
	public $blockY;
	public $blockZ;

	public $sideHit;

	/** @var Vector3 */
	public $hitVector;

	/** @var Entity */
	public $entityHit = null;

	/**
	 * MovingObjectPosition constructor.
	 */
	protected function __construct(){

	}

	/**
	 * @param int     $x
	 * @param int     $y
	 * @param int     $z
	 * @param int     $side
	 * @param Vector3 $hitVector
	 *
	 * @return MovingObjectPosition
	 */
	public static function fromBlock($x, $y, $z, $side, Vector3 $hitVector){
		$ob = new MovingObjectPosition;
		$ob->typeOfHit = 0;
		$ob->blockX = $x;
		$ob->blockY = $y;
		$ob->blockZ = $z;
		$ob->hitVector = $hitVector->asVector3();
		return $ob;
	}

	/**
	 * @param Entity $entity
	 *
	 * @return MovingObjectPosition
	 */
	public static function fromEntity(Entity $entity){
		$ob = new MovingObjectPosition;
		$ob->typeOfHit = 1;
		$ob->entityHit = $entity;
		$ob->hitVector = $entity->asVector3();
		return $ob;
	}
}
