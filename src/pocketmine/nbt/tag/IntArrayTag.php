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

class IntArrayTag extends NamedTag {

	/**
	 * @return int
	 */
	public function getType(){
		return NBT::TAG_IntArray;
	}

	/**
	 * @param NBT  $nbt
	 * @param bool $network
	 *
	 * @return mixed|void
	 */
	public function read(NBT $nbt, bool $network = false){
		$size = $nbt->getInt($network);
		$this->value = array_values(unpack($nbt->endianness === NBT::LITTLE_ENDIAN ? "V*" : "N*", $nbt->get($size * 4)));
	}

	/**
	 * @param NBT  $nbt
	 * @param bool $network
	 *
	 * @return mixed|void
	 */
	public function write(NBT $nbt, bool $network = false){
		$nbt->putInt(count($this->value), $network);
		$nbt->put(pack($nbt->endianness === NBT::LITTLE_ENDIAN ? "V*" : "N*", ...$this->value));
	}

	/**
	 * @return string
	 */
	public function __toString(){
		$str = get_class($this) . "{\n";
		$str .= implode(", ", $this->value);
		return $str . "}";
	}
}