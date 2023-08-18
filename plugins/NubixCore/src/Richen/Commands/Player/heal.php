<?php 

namespace Richen\Commands\Player;

class heal extends \Richen\NubixCmds  {
    public function __construct($name) { parent::__construct($name, 'Восстановить здоровье'); }
    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof \pocketmine\Player) return $sender->sendMessage($this->getConsoleUsage());
        $sender->setHealth($sender->getMaxHealth());
        $sender->sendMessage($this->lang()::SUC . ' §aВаше здоровье восстановлено');
    }
}