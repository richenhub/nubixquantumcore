<?php 

namespace Richen\Commands;

class job extends \Richen\NubixCmds {

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
    }
}