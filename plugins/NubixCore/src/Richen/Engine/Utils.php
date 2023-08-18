<?php

namespace Richen\Engine;

use pocketmine\event\entity\EntityDamageEvent;

class Utils {
    public static function hash(string $string1, string $string2) { return hash('sha256', $string1 . mb_strtoupper($string2) . 'bchnhb'); }

    public static function roundvalue($value): string
    {
        if ($value < 5000) {
            $newVal = sprintf('%d', $value);
        } elseif ($value < 10000) {
            $value = $value / 1000;
            $newVal = number_format($value, 3, '.', '') . 'k';
        } elseif ($value < 300000) {
            $value = $value / 1000;
            $newVal = number_format($value, 2, '.', '') . 'k';
        } elseif ($value < 1000000) {
            $value = $value / 1000;
            $newVal = number_format($value, 1, '.', '') . 'k';
        } elseif ($value >= 1000000 && $value < 1000000000) {
            $value = $value / 1000000;
            $newVal = number_format($value, 1) . 'M';
        } elseif ($value >= 1000000000 && $value < 1000000000000) {
            $value = $value / 1000000000;
            $newVal = number_format($value, 1) . 'B';
        } elseif ($value >= 1000000000000) {
            $newVal = sprintf('%d%s', floor($value / 1000000000000), 'T+∞');
        }
        return $newVal ?? '0';
    }

    public static function sec2Time($value, $withYears = true, $withMonths = true, $withDays = true, $withHours = true, $withMinutes = true, $withSeconds = true) {
        $years = floor($value / 31536000);
        $value = $value % 31536000;
        $months = floor($value / 2592000);
        $value = $value % 2592000;
        $days = floor($value / 86400);
        $value = $value % 86400;
        $hours = floor($value / 3600);
        $value = $value % 3600;
        $minutes = floor($value / 60);
        $value = $value % 60;
        $seconds = $value;
        $timeFormat = "";
        if ($withYears) { $timeFormat .= "$years г. "; }
        if ($withMonths) { $timeFormat .= "$months мес. "; }
        if ($withDays) { $timeFormat .= "$days д. "; }
        if ($withHours) { $timeFormat .= "$hours ч. "; }
        if ($withMinutes) { $timeFormat .= "$minutes м. "; }
        if ($withSeconds) { $timeFormat .= "$seconds с."; }
        
        return trim($timeFormat);
    }

    public static function strtime2Sec($timeFormat) {
        $seconds = 0;
        if (preg_match_all('/(\d+)\s*([a-z]+)/', $timeFormat, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $value = intval($match[1]);
                $unit = $match[2];
                switch ($unit) {
                    case 'y': $seconds += $value * 31536000; break;
                    case 'mes': $seconds += $value * 2592000; break;
                    case 'd': $seconds += $value * 86400; break;
                    case 'h': $seconds += $value * 3600; break;
                    case 'm': $seconds += $value * 60; break;
                    case 's': $seconds += $value; break;
                }
            }
        }
        return $seconds;
    }

    public static function strToPosition(string $position): ?\pocketmine\level\Position {
        if (preg_match('/^Position\(level=(.*),x=(.*),y=(.*),z=(.*)\)$/', $position, $match)) {
            $worldName = $match[1];
            $world = \pocketmine\Server::getInstance()->getLevelByName($worldName);
            if (!$world) return null;
            $x = (int) $match[2];
            $y = (int) $match[3];
            $z = (int) $match[4];
            $position = new \pocketmine\level\Position($x, $y, $z, $world);
            $position->add(.5,.5,.5);
            return $position;
        }
        return null;
    }

    public static function getCause(int $cause, \pocketmine\Player $player, $damager = null, \pocketmine\entity\Living $entity = null): string {
		$reason = "";
		switch ($cause) {
			case EntityDamageEvent::CAUSE_CONTACT:
				$reason = "{prefix} §fИгрок §6{player} §fпал от кактуса";
				break;
			case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
				if ($damager instanceof \pocketmine\Player) {
					$damager = $damager->getName();
					$reason = "{prefix} §fИгрок §6{player} §fбыл убит";
					break;
				}
				$reason = "{prefix} §fИгрок §6{player} §fбыл убит §6{entity}";
				break;
			case EntityDamageEvent::CAUSE_PROJECTILE:
				$reason = "{prefix} §fИгрок §6{player} §fбыл застрелен";
				break;
			case EntityDamageEvent::CAUSE_SUFFOCATION:
				$reason = "{prefix} §fИгрок §6{player} §fзадохнулся в блоках";
				break;
			case EntityDamageEvent::CAUSE_FALL:
				$reason = "{prefix} §fИгрок §6{player} §fразбился об землю";
				break;
			case EntityDamageEvent::CAUSE_FIRE:
			case EntityDamageEvent::CAUSE_FIRE_TICK:
			$reason = "{prefix} §fИгрок §6{player} §fсгорел";
				break;
			case EntityDamageEvent::CAUSE_LAVA:
				$reason = "{prefix} §fИгрок §6{player} §fрешил покупаться в лаве";
				break;
			case EntityDamageEvent::CAUSE_DROWNING:
				$reason = "{prefix} §fИгрок §6{player} §fутонул";
				break;
			case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
			case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
				$reason = "{prefix} §fИгрок §6{player} §fвзорвался";
				break;
			case EntityDamageEvent::CAUSE_VOID:
				$reason = "{prefix} §fИгрок §6{player} §fдумал, что жить в бездне возможно";
				break;
			case EntityDamageEvent::CAUSE_SUICIDE:
				$reason = "{prefix} §fИгрок §6{player} §fналожил на себя руки";
				break;
			case EntityDamageEvent::CAUSE_MAGIC:
				$reason = "{prefix} §fИгрок §6{player} §fумер, сражаясь с потусторонними силами";
				break;
			case EntityDamageEvent::CAUSE_STARVATION:
				$reason = "{prefix} §fИгрок §6{player} §fумер от неимения денег на еду";
				break;
			case EntityDamageEvent::CAUSE_CUSTOM:
				$reason = "{prefix} §fИгрок §6{player} §fумер по неизвестной причине 0_0";
				break;
		}

		if ($damager !== null) {
			$reason = $reason . ", сражаясь с игроком " . $damager;
		}

		return str_replace(["{prefix}", "{player}", "{killer}", "{entity}"], ['§d☣', $player->getName(), $damager, $entity ? $entity->getName() : ''], $reason);
	}

    public static function rainbowify2($str, $offset = 0) {
        $colors = array('§4', '§c', '§6', '§e', '§2', '§a', '§b', '§3', '§d', '§5');
        $colorIndex = $offset;
		$rainbow = '';
		for ($i = 0; $i < mb_strlen($str); $i++) {
			$color = $colors[$colorIndex % count($colors)];
			$rainbow .= $color . mb_substr($str, $i, 1);
            $colorIndex = $colorIndex === count($colors) ? 0 : $colorIndex + 1;
		}
		return $rainbow;
	}

    public static function rainbowify($str, $offset = 0, $changeEvery = 3) {
        $colors = array('§4', '§c', '§6', '§e', '§2', '§a', '§b', '§3', '§d', '§5');
        $colorIndex = $offset;
        $rainbow = '';
        for ($i = 0; $i < mb_strlen($str); $i += $changeEvery) {
            $color = $colors[$colorIndex % count($colors)];
            $substring = mb_substr($str, $i, $changeEvery);
            $rainbow .= $color . $substring;
            $colorIndex = $colorIndex === count($colors) ? 0 : $colorIndex + 1;
        }
        return $rainbow;
    }

    public static function isNumber($value, int $min = null, int $max = null) {
        if (!is_numeric($value)) {
            return '§4[!] §cЗначение должно быть числом';
        }
        if ($min !== null && $value < $min) {
            return '§4[!] §cЗначение должно быть не меньше §6' . $min;
        }
        if ($max !== null && $value > $max) {
            return '§4[!] §cЗначение должно быть не больше §6' . $max;
        }
        return $value;
    }
}