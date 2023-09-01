<?php declare(strict_types=1);

namespace richen\managers;

class easyauth extends manager
{
    public function isRegistered(): bool
    {

        return false;
    }

    public function isAuth(\pocketmine\Player $player): bool
    {
        if ($this->isCustomPlayer($player)) {


            return true;
        }

        return false;
    }
}