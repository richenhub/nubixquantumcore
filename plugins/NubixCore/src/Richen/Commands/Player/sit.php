<?php 

namespace Richen\Commands\Player;

class sit extends \Richen\NubixCmds  {
    public function __construct($name) { parent::__construct($name, 'Присесть'); }
    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof \Richen\Custom\NBXPlayer) return $sender->sendMessage($this->getConsoleUsage());
        $sender->sitHere();
    }
}