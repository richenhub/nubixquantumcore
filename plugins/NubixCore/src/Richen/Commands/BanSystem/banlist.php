<?php 

namespace Richen\Commands\BanSystem;

use pocketmine\player\Player;
use NBX\Utils\Values;
use Richen\Engine\Utils;

class banlist extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Список заблокированных'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        $bm = $this->core()->bans();
        if (!count($args) || !$bm->hasType($args[0])) return $sender->sendMessage($this->getUsageMessage('[тип] (1 - банлист, 2 - мутлист)'));
        $list = $bm->getBanList($args[0]);
        if (empty($list)) return $sender->sendMessage('§4[!] §cСписок ' . $args[0] . ' пуст');
        switch ($args[0]) {
            case $bm::BAN: $prefix = 'Заблокированные игроки'; break;
            case $bm::MUTE: $prefix = 'Игроки в муте'; break;
        }
        $page = max(1, min(isset($args[1]) && is_numeric($args[1]) ? intval($args[1]) : 1, ceil(count($list) / 7)));
        $dataPage = array_slice($list, ($page - 1) * 7, 7);
        $sender->sendMessage('§6[!] §2' . $prefix . '§7. Всего §6' . count($list) . '§7. Показана страница §e' . $page . '§7:');
        foreach ($dataPage as $i => $info) {
            $sender->sendMessage('§7' . (($page - 1) * 7 + $i + 1) . ') §e' . $info['username'] . ' §8/ §7причина: §f' . $info['reason'] . '. §7Разбан через: §f' . Utils::sec2Time(($info[$bm->types[$args[0]]] - time()), false, false));
        }
    }
}