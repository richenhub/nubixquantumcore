<?php 

namespace Richen\Engine\Traits;

use pocketmine\utils\TextFormat as C;

trait Helper {
    public function core(): \Richen\NubixCore { return \Richen\NubixCore::core(); }
    public function log(string $message): void { \Richen\NubixCore::core()->getLogger()->info($message); }
    public function hash(string $string1, string $string2): string { return hash('sha256', $string1 . mb_strtoupper($string2) . 'bchnhb'); }
    public function strToPosition(string $position): ?\pocketmine\level\Position {
        if (!preg_match('/^Position\(level=(.*),x=(.*),y=(.*),z=(.*)\)$/', $position, $pm)) return null;
        if (!$world = \pocketmine\Server::getInstance()->getLevelByName($pm[1])) return null;
        return (new \pocketmine\level\Position((int) $pm[2], (int) $pm[3], (int) $pm[4], $world))->add(.5, .5, .5);
    }
    public function getOnlinePlayers(): array { return $this->core()->serv()->getOnlinePlayers(); }
}