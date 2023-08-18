<?php 

namespace Richen\Commands\System;

class setspawn extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Установка точки спавна в мире'); }
    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if ($sender instanceof \pocketmine\Player) {
            $position = $sender->getPosition()->__toString();
            $worldName = $sender->getLevel()->getName();
            $serverData = $this->core()->getServerData();
            $serverData['spawns'][$worldName] = $position;
            $this->core()->setServerData($serverData);
            $sender->sendMessage('§2[!] §7Точка спавна для мира §e' . $sender->getLevel()->getName() . ' §7успешно установлена');
        } else {
            $sender->sendMessage('§4[!] §cКоманда используется только в игре');
        }
    }
}