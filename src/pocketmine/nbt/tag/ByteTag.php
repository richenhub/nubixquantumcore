<?php
/*
░██╗░░░░░░░██╗███╗░░░███╗ 
░██║░░██╗░░██║████╗░████║ 
░╚██╗████╗██╔╝██╔████╔██║ 
░░████╔═████║░██║╚██╔╝██║ 
░░╚██╔╝░╚██╔╝░██║░╚═╝░██║ 
░░░╚═╝░░░╚═╝░░╚═╝░░░░░╚═╝
*/

namespace pocketmine\nbt\tag;

use pocketmine\nbt\NBT;

#include <rules/NBT.h>

class ByteTag extends NamedTag {

	/**
	 * @return int
	 */
	public function getType(){
		return NBT::TAG_Byte;
	}

	/**
	 * @param NBT  $nbt
	 * @param bool $network
	 *
	 * @return mixed|void
	 */
	public function read(NBT $nbt, bool $network = false){
		$this->value = $nbt->getSignedByte();
	}

	/**
	 * @param NBT  $nbt
	 * @param bool $network
	 *
	 * @return mixed|void
	 */
	public function write(NBT $nbt, bool $network = false){
		$nbt->putByte($this->value);
	}
}