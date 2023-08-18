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

class LongTag extends NamedTag {

	/**
	 * @return int
	 */
	public function getType(){
		return NBT::TAG_Long;
	}

	/**
	 * @param NBT  $nbt
	 * @param bool $network
	 *
	 * @return mixed|void
	 */
	public function read(NBT $nbt, bool $network = false){
		$this->value = $nbt->getLong($network);
	}

	/**
	 * @param NBT  $nbt
	 * @param bool $network
	 *
	 * @return mixed|void
	 */
	public function write(NBT $nbt, bool $network = false){
		$nbt->putLong($this->value, $network);
	}

	/**
	 * @return int
	 */
	public function &getValue() : int{
		return parent::getValue();
	}

	/**
	 * @param int $value
	 *
	 * @throws \TypeError
	 */
	public function setValue($value) : void{
		if(!is_int($value)){
			throw new \TypeError("LongTag value must be of type int, " . gettype($value) . " given");
		}
		parent::setValue($value);
	}
}