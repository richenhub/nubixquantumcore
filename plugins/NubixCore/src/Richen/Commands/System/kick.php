<?php 

namespace Richen\Commands\System;

class kick extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Кикнуть игрока'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (count($args) < 2) {
            return $sender->sendMessage($this->getUsageMessage('[игрок] [причина]'));
        }
        $name = mb_strtolower($args[0]);

        $pl = $this->getPlayerByName($name);
        if (!$pl) return $sender->sendMessage($this->getOfflineMessage($name));
        $sendername = mb_strtolower($sender->getName());
        $targetname = mb_strtolower($args[0]);
        $reason = implode(' ', array_slice($args, 2));
        $res = $this->core()->bans()->setBan(3, $sendername, $targetname, $reason, time(), $pl->getAddress(), $pl->getClientId());
        $message = '§3[§6NXBAN§3] §6Игрок с ником §c' . $targetname . ' §6был кикнут игроком §3' . $sendername . '§6. Причина: §f' . $reason;
        $sender->getServer()->broadcastMessage($message);
        $pl->kick('§fВы кикнуты игроком §6' . $sendername . PHP_EOL . '§f. Причина: §e' . $reason);
        if (!is_numeric($res)) {
            $sender->sendMessage($res);
        }
    }
}