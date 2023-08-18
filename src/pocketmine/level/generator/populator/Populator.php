<?php

namespace pocketmine\level\generator\populator;

use pocketmine\level\loadchunk\ChunkManager;
use pocketmine\utils\Random;

abstract class Populator {
	public abstract function populate(ChunkManager $level, $chunkX, $chunkZ, Random $random);
}