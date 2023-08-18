<?php 

namespace Richen\Commands\Player\Teleport;

use Richen\Engine\Utils;

class spawn extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Телепортация на спавн'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof \Richen\Custom\NBXPlayer) return $sender->sendMessage($this->getConsoleUsage());
        $serverData = $this->core()->getServerData();
        $position = Utils::strToPosition($serverData['spawns'][$sender->getLevel()->getName()] ?? 'world');
        if (!$position instanceof \pocketmine\level\Position) return $sender->sendMessage($this->getErrorMessage());
        $sender->getLastPosManager()->setLastPosition($sender->getPosition());
        $sender->teleportManager()->teleport($position, 'Телепортация на §6спавн %s', ' §7Телепортация на §6спавн §7прошла успешно');
    }
}