<?php

namespace pocketmine\network;

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\Player;

interface SourceInterface{

public function start();
	 /*
	 * @param Player     $player
	 * @param DataPacket $packet
	 * @param bool       $needACK
	 * @param bool       $immediate
	 *
	 * @return int
	 */
public function putPacket(Player $player, DataPacket $packet, bool $needACK = false, bool $immediate = true);
    /*
	 * @param Player $player
	 * @param string $reason
	 *
	 */
public function close(Player $player, $reason = "unknown reason");
public function setName(string $name);
public function process() : void;
public function shutdown();
public function emergencyShutdown();
}