<?php 

namespace Richen\Commands\Player\Teleport;

class back extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Телепортация на последнюю точку'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof \Richen\Custom\NBXPlayer) return $sender->sendMessage($this->getConsoleUsage());
        $posManager = $sender->getLastPosManager();
        if (!$posManager->hasPosition()) return $sender->sendMessage('§4[!] §cУ вас нет последних точек для телепортации');
        $pos = $posManager->getLastPosition();
        if (!$pos instanceof \pocketmine\level\Position) $sender->sendMessage($this->getErrorMessage());
        $sender->teleportManager()->teleport($pos, 'Телепортация на §6последнюю точку %s', 'Телепортация на §6последнюю точку §7прошла успешно');
        $posManager->delLastPosition();
    }
}