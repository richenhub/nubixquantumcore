<?php 

namespace Richen\Commands\Player\Chat;

use Richen\Engine\Filter;

class tell extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Точка дома'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (count($args) < 2) return $sender->sendMessage($this->getUsageMessage('[игрок] [сообщение]'));
        $name = array_shift($args);
        $message = implode(' ', $args);
        if (mb_strlen($message) < 3) return $sender->sendMessage($this->lang()::ERR . ' §cСообщение не может быть таким коротким');
        $player = $this->getPlayerByName($name);
        if (!$player) return $sender->sendMessage($this->getOfflineMessage($name));
        $filter = Filter::getFiltered($message);
        if (!$filter['isallowed']) $sender->sendMessage($this->lang()::ERR . ' §cНа нашем сервере запрещены оскорбительные выражения');
        if ($player === $sender) return $sender->sendMessage($this->lang()::ERR . ' §cВы не можете отправить сообщение самому себе');
        $rec = [];
        $rec[] = $player;
        $rec[] = $sender;
        foreach ($this->getOnlinePlayers() as $pl) {
            if ($pl === $sender || $pl === $player) continue;
            if ($this->hasPermission($sender, '.other')) $rec[] = $pl;
        }
        $this->serv()->broadcastMessage('§8[§3Личный§7-§fЧат§8] §b' . $sender->getName() . ' §7> §f' . $player->getName() . '§8: §7' . $filter['message'], $rec);
    }
}