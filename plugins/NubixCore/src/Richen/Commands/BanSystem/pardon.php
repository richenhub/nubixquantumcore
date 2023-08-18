<?php 

namespace Richen\Commands\BanSystem;

class pardon extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Разблокировать игрока'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (count($args) < 2) return $sender->sendMessage($this->getUsageMessage('[игрок] [комментарий]'));
        $sendername = mb_strtolower($sender->getName());
        $username = mb_strtolower($args[0]);
        $reason = implode(' ', array_slice($args, 1));
        $res = $this->core()->bans()->delBan(1, $sendername, $username, $reason);
        if (is_numeric($res)) {
            $message = '§3[§6NXBAN§3] §7Игрок с ником §6' . $username . ' §7разблокирован игроком §3' . $sendername . '§7. Комментарий: §f' . $reason;
            $sender->getServer()->broadcastMessage($message);
        } else {
            $sender->sendMessage($res);
        }
    }
}