<?php

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>


class ExplodePacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::EXPLODE_PACKET;

	public $x;
	public $y;
	public $z;
	/** @var float */
	public $radius;
	/** @var Vector3[] */
	public $records = [];

	/**
	 * @return $this
	 */
	public function clean(){
		$this->records = [];
		return parent::clean();
	}

	/**
	 *
	 */
	public function decode(){

	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putVarInt((int) ($this->radius * 32));
		$this->putUnsignedVarInt(count($this->records));
		if(count($this->records) > 0){
			foreach($this->records as $record){
				$this->putBlockCoords((int) $record->x, (int) $record->y, (int) $record->z);
			}
		}
	}

	/**
	 * @return string Current packet name
	 */
	public function getName(){
		return "ExplodePacket";
	}

}