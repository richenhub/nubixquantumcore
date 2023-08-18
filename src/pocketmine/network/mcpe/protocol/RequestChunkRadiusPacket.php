<?php

namespace pocketmine\network\mcpe\protocol;

class RequestChunkRadiusPacket extends DataPacket {

	const NETWORK_ID = ProtocolInfo::REQUEST_CHUNK_RADIUS_PACKET;

	public $radius;

	/**
	 *
	 */
	public function decode(){
		$this->radius = $this->getVarInt();
		if ($this->radius > 100) $this->raduis = 1;
	}

	public function encode(){

	}

}