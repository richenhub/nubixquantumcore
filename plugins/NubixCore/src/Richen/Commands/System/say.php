<?php 

namespace Richen\Commands\System;

class say extends \Richen\NubixCmds  {
    public function __construct($name) { parent::__construct($name, 'Сообщение всем'); }
    private $timeout = [];
    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!count($args)) return $sender->sendMessage($this->getUsageMessage('[сообщение]'));
        if (isset($this->timeout[$sender->getName()]) && $this->timeout[$sender->getName()] - time() > 0) return $sender->sendMessage('§4[!] §cПодождите ещё ' . ($this->timeout[$sender->getName()] - time()) . ' сек. для повторного использования');
        $prefix = $sender instanceof \pocketmine\command\ConsoleCommandSender ? '§7[§bНУБИКС АЛЕРТС§7]' : '§7[§fИгрок §e' . $sender->getName() . ' §7> §bВСЕМ§7]';
        $this->core()->getServer()->broadcastMessage($prefix . '§7: §f' . implode(' ', $args));
        $this->timeout[$sender->getName()] = time() + 60;
    }
}