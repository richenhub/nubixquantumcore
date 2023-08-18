<?php 

namespace Richen\Commands\Player\Items;

class kit extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Кит старты'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof \Richen\Custom\NBXPlayer) return $sender->sendMessage($this->getConsoleUsage());
        switch ($args[0] ?? 'help') {
            case 'start':
                $h = Item::get();
                $c = Item::get();
                $f = Item::get();
                $b = 
                break;
            case 'vip':

                break;
            case 'bonus':

                break;
        }
    }
}