<?php 

namespace Richen\Commands\Player;

class feed extends \Richen\NubixCmds  {
    public function __construct($name) { parent::__construct($name, 'Восстановить голод'); }
    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof \pocketmine\Player) return $sender->sendMessage($this->getConsoleUsage());
        $sender->setFood($sender->getMaxFood());
        $sender->sendMessage($this->lang()::SUC . ' §aВы восстановили голод');
    }
}