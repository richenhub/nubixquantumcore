<?php

namespace pocketmine\network\mcpe;

use raklib\protocol\EncapsulatedPacket;

class CachedEncapsulatedPacket extends EncapsulatedPacket{

	/** @var string|null */
	private $internalData = null;

	public function toInternalBinary() : string{
		return $this->internalData ?? ($this->internalData = parent::toInternalBinary());
	}
}