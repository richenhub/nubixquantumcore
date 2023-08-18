<?php

namespace Richen\Engine\Tasks;
use Richen\Custom\NBXPlayer;

class BossBarTask extends TaskManager {
    public $core;
	private $bossbar;
	private $index = 0;
	public function __construct() { $this->bossbar = new \Richen\Engine\Additions\BossBar(); }
	private $percentage = 100;
	public function getMessage($index): string {
		$bb = $this->bossbar;
		$messages = $bb->getMessages();
		$strings = $messages[$index];
		foreach ($strings as $string) if (($length = mb_strlen($string)) > ($max_length ?? 0)) $max_length = $length;
		foreach ($strings as $string) $msgarr[] = str_repeat(' ', ($padding = (($max_length ?? 0) - mb_strlen($string)) / 2)) . $string . str_repeat(' ', $padding);
		$msgarr[0] = $msgarr[0] . PHP_EOL;
		return implode(PHP_EOL, $msgarr);
	}
	
	public function onRun($tick): void {
		$bb = $this->bossbar;
		if ($this->percentage <= 0) {
			$this->percentage = 100;
		} else {
			$this->percentage -= 10;
		}
		if (count($bb->getMessages()) <= $this->index) $this->index = 0;
		foreach (\pocketmine\Server::getInstance()->getOnlinePlayers() as $player) {
			if ($player instanceof NBXPlayer && $player->isauth()) {
				$name = mb_strtolower($player->getName());
				if ($this->percentage === 0) {
					$bb->sendBossBarToPlayer($player, $bb->getEid($name), '§enubix.ru');
				}
				if (!$player->inFight()) {
					$bb->setPercentage($this->percentage, $bb->getEid($name));
					$bb->setTitle($this->getMessage($this->index), $bb->getEid($name));
				}
			} else {
				$bb->removeBossBar($bb->getEid($player->getName()));
			}
		}
		if ($this->percentage === 0) {
			$this->index++;
		}
	}

	
}