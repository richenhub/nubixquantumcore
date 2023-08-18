<?php

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>


class MoveEntityPacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::MOVE_ENTITY_PACKET;

	public $eid;

	public $x;
	public $y;
	public $z;

	public $yaw;
	public $headYaw;
	public $pitch;

	public $onGround;
	public $teleported;

	/**
	 *
	 */
	public function decode(){
		$this->eid = $this->getEntityId();
		$this->getVector3f($this->x, $this->y, $this->z);
		$this->pitch = $this->getByte() * (360.0 / 256);
		$this->yaw = $this->getByte() * (360.0 / 256);
		$this->headYaw = $this->getByte() * (360.0 / 256);
		$this->onGround = $this->getBool();
		$this->teleported = $this->getBool();
	}

	/**
	 *
	 */
	public function encode(){
		$this->reset();
		$this->putEntityId($this->eid);
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putByte($this->pitch / (360.0 / 256));
		$this->putByte($this->yaw / (360.0 / 256));
		$this->putByte($this->headYaw / (360.0 / 256));
		$this->putBool($this->onGround);
		$this->putBool($this->teleported);
	}

}
