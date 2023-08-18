<?php

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;

class StatusCommand extends VanillaCommand {

	/**
	 * StatusCommand constructor.
	 *
	 * @param $name
	 */
	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.status.description",
			"%pocketmine.command.status.usage"
		);
		$this->setPermission("pocketmine.command.status");
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $currentAlias
	 * @param array         $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, $currentAlias, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		$rUsage = Utils::getRealMemoryUsage();
		$mUsage = Utils::getMemoryUsage(true);

		$server = $sender->getServer();
		$sender->sendMessage(TextFormat::WHITE . "---- " . TextFormat::GREEN . "%pocketmine.command.status.title" . TextFormat::WHITE . " ----");

		$time = (int) (microtime(true) - \pocketmine\START_TIME);

		$seconds = $time % 60;
		$minutes = null;
		$hours = null;
		$days = null;

		if($time >= 60){
			$minutes = floor(($time % 3600) / 60);
			if($time >= 3600){
				$hours = floor(($time % (3600 * 24)) / 3600);
				if($time >= 3600 * 24){
					$days = floor($time / (3600 * 24));
				}
			}
		}

		$uptime = ($minutes !== null ?
				($hours !== null ?
					($days !== null ?
						"$days days "
					: "") . "$hours hours "
					: "") . "$minutes minutes "
			: "") . "$seconds seconds";

		$sender->sendMessage(TextFormat::GREEN . "%pocketmine.command.status.uptime " . TextFormat::GOLD . $uptime);

		$tpsColor = TextFormat::WHITE;
		if($server->getTicksPerSecond() < 17){
			$tpsColor = TextFormat::GREEN;
		}elseif($server->getTicksPerSecond() < 12){
			$tpsColor = TextFormat::GOLD;
		}

		$sender->sendMessage(TextFormat::GREEN . "%pocketmine.command.status.CurrentTPS " . $tpsColor . $server->getTicksPerSecond() . " (" . $server->getTickUsage() . "%)");
		$sender->sendMessage(TextFormat::GREEN . "%pocketmine.command.status.AverageTPS " . $tpsColor . $server->getTicksPerSecondAverage() . " (" . $server->getTickUsageAverage() . "%)");

		$onlineCount = 0;
		foreach($sender->getServer()->getOnlinePlayers() as $player){
			if($player->isOnline() and (!($sender instanceof Player) or $sender->canSee($player))){
				++$onlineCount;
			}
		}

		$sender->sendMessage(TextFormat::GREEN . "%pocketmine.command.status.player" . TextFormat::WHITE . " " . $onlineCount . "/" . $sender->getServer()->getMaxPlayers());
		$sender->sendMessage(TextFormat::GREEN . "%pocketmine.command.status.Networkupload " . TextFormat::GOLD . \round($server->getNetwork()->getUpload() / 1024, 2) . " kB/s");
		$sender->sendMessage(TextFormat::GREEN . "%pocketmine.command.status.Networkdownload " . TextFormat::GOLD . \round($server->getNetwork()->getDownload() / 1024, 2) . " kB/s");
		$sender->sendMessage(TextFormat::GREEN . "%pocketmine.command.status.Threadcount " . TextFormat::GOLD . Utils::getThreadCount());
		$sender->sendMessage(TextFormat::GREEN . "%pocketmine.command.status.Mainmemory " . TextFormat::GOLD . number_format(round(($mUsage[0] / 1024) / 1024, 2), 2) . " MB.");
		$sender->sendMessage(TextFormat::GREEN . "%pocketmine.command.status.Totalmemory " . TextFormat::GOLD . number_format(round(($mUsage[1] / 1024) / 1024, 2), 2) . " MB.");
		$sender->sendMessage(TextFormat::GREEN . "%pocketmine.command.status.Totalvirtualmemory " . TextFormat::GOLD . number_format(round(($mUsage[2] / 1024) / 1024, 2), 2) . " MB.");
		$sender->sendMessage(TextFormat::GREEN . "%pocketmine.command.status.Heapmemory " . TextFormat::GOLD . number_format(round(($rUsage[0] / 1024) / 1024, 2), 2) . " MB.");

		if($server->getProperty("memory.global-limit") > 0){
			$sender->sendMessage(TextFormat::GREEN . "%pocketmine.command.status.Maxmemorymanager " . TextFormat::GOLD . number_format(round($server->getProperty("memory.global-limit"), 2), 2) . " MB.");
		}

		foreach($server->getLevels() as $level){
			$levelName = $level->getFolderName() !== $level->getName() ? " (" . $level->getName() . ")" : "";
			$timeColor = $level->getTickRateTime() > 40 ? TextFormat::GOLD : TextFormat::YELLOW;
			$sender->sendMessage(TextFormat::GREEN . "Мир \"{$level->getFolderName()}\"$levelName: " .
				TextFormat::GOLD . number_format(count($level->getChunks())) . TextFormat::WHITE . " %pocketmine.command.status.chunks " .
				TextFormat::GOLD . number_format(count($level->getEntities())) . TextFormat::WHITE . " %pocketmine.command.status.entities " .
				TextFormat::GOLD . number_format(count($level->getTiles())) . TextFormat::WHITE . " %pocketmine.command.status.tiles " .
				"%pocketmine.command.status.Time " . round($level->getTickRateTime(), 2) . "ms"
			);
		}

		return true;
	}
}
