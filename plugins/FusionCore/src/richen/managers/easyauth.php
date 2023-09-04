<?php declare(strict_types=1);

namespace richen\managers;

use richen\managers\easyauth\easyauthquery;

class easyauth extends manager
{
    public ?easyauthquery $query = null;

    public function __construct()
    {
        $this->query = new easyauthquery();
    }

    public function getUser(\pocketmine\Player $player)
    {
        $user = $this->query->getUserByName($player->getName());

        return $user;
    }

    public function isRegistered(\pocketmine\Player $player): bool
    {
        if ($user = $this->getUser($player)) {
            if ($user->password) {
                return true;
            }
        }

        return false;
    }

    public function isAuth(\pocketmine\Player $player): bool
    {
        if ($this->isCustomPlayer($player)) {
            if ($user = $this->getUser($player)) {
                if ($user->address !== $player->getAddress()) {
                    return false;
                }

                if (time() - $user->lastlogin > 86400) {
                    return false;
                }

                return true;
            }
        }

        return false;
    }
}