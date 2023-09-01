<?php declare(strict_types=1);

namespace richen\custom;

class customplayer extends \pocketmine\Player
{
	public function getLowerCaseName(): string
    {
        return mb_strtolower($this->iusername);
    }
}