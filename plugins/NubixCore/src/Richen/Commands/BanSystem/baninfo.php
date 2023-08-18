<?php 

namespace Richen\Commands\BanSystem;

class baninfo extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Информация о бане'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!count($args)) return $sender->sendMessage($this->getUsageMessage('[игрок]'));
        $sender->sendMessage($this->core()->bans()->getBanInfo($args[0]));
    }
}