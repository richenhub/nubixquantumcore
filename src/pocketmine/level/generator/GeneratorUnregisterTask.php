<?php

declare(strict_types = 1);

namespace pocketmine\level\generator;


use pocketmine\level\Level;
use pocketmine\scheduler\AsyncTask;


class GeneratorUnregisterTask extends AsyncTask{

	public $levelId;

	/**
	 * GeneratorUnregisterTask constructor.
	 *
	 * @param Level $level
	 */
	public function __construct(Level $level){
		$this->levelId = $level->getId();
	}

	public function onRun(){
		$this->removeFromThreadStore("generation.level{$this->levelId}.manager");
		$this->removeFromThreadStore("generation.level{$this->levelId}.generator");
	}
}
