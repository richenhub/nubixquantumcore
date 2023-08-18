<?php 

namespace Richen\Engine;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;
use Richen\Custom\NBXPlayer;

abstract class Manager {
    public function __construct() {}

    public function core(): \Richen\NubixCore { return \Richen\NubixCore::core(); }
    public function data(): \Richen\NubixData { return \Richen\NubixCore::data(); }
    public function lang(): \Richen\NubixLang { return \Richen\NubixCore::lang(); }
    public function serv(): \pocketmine\Server { return $this->core()->getServer(); }
    public function hasPermission($player, string $perm): bool {
        if ($player instanceof NBXPlayer || $player instanceof Player) {
            return $player->hasPermission($perm) || $player->getServer()->isOp($player->getName());
        } elseif ($player instanceof ConsoleCommandSender) {
            return true;
        }
        return false;
    }
}