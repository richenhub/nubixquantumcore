<?php 

declare(strict_types=1);

namespace richen\helpers;

trait math {
    public function hash(string $string1, string $string2): string {
        return hash('sha256', $string1 . mb_strtoupper($string2) . 'bchnhb');
    }

    public function strToPosition(string $position): ?\pocketmine\level\Position {
        if (!preg_match('/^Position\(level=(.*),x=(.*),y=(.*),z=(.*)\)$/', $position, $pm)) {
            return null;
        }

        if (!$world = \pocketmine\Server::getInstance()->getLevelByName($pm[1])) {
            return null;
        }

        if (!$world instanceof \pocketmine\level\Level) {
            return null;
        }

        return $this->getPosition($pm[2], $pm[3], $pm[4], $world);
    }

    public function getPosition($x, $y, $z, \pocketmine\level\Level $world): \pocketmine\level\Position {
        $position = new \pocketmine\level\Position((int) $x, (int) $y, (int) $z, $world);

        return $position;
    }
}