<?php 

namespace Richen\Commands\System;

use Richen\Engine\Utils;

class online extends \Richen\NubixCmds {
    public function __construct($name) {
        parent::__construct($name, 'Список игроков онлайн', ['list']);
    }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        $list = $this->core()->getServer()->getOnlinePlayers();
        if (empty($list)) return $sender->sendMessage('§4[!] §cНа сервере нет игроков онлайн');
        $page = max(1, min(isset($args[0]) && is_numeric($args[0]) ? intval($args[0]) : 1, ceil(count($list) / 10)));
        $dataPage = array_slice($list, ($page - 1) * 10, 10);
        $sender->sendMessage('§6[!] §7На сервере сейчас онлайн §6' . count($list) . '§7 игроков. Показана страница §e' . $page . '§7:');
        $i = 0;
        foreach ($dataPage as $player) {
            if ($player instanceof \Richen\Custom\NBXPlayer) { $sender->sendMessage('§7' . (($page - 1) * 10 + $i + 1) . ') §e' . $player->getDisplayName() . '§7. Отыграно: §f' . Utils::sec2Time($player->getPlayerStats()->getOnline(time() - $player->getLastLogin()), false, false)); }
            $i++;
        }
    }
}