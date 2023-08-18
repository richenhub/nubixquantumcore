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

class EndTag extends Tag {

	/**
	 * @return int
	 */
	public function getType(){
		return NBT::TAG_End;
	}

	/**
	 * @param NBT  $nbt
	 * @param bool $network
	 *
	 * @return mixed|void
	 */
	public function read(NBT $nbt, bool $network = false){

	}

	/**
	 * @param NBT  $nbt
	 * @param bool $network
	 *
	 * @return mixed|void
	 */
	public function write(NBT $nbt, bool $network = false){

	}
}