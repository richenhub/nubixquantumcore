<?php 

namespace Richen\Commands\Player\Home;

use Richen\Custom\NBXPlayer;
use Richen\NubixCmds;

class sethome extends NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Установка точки дома'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (count($args) < 1) {
            return $sender->sendMessage($this->getUsageMessage('[название]'));
        }
        if ($sender instanceof NBXPlayer) {
            $homeManager = $sender->getHomeManager();
            $isHome = $homeManager->isHome($args[0]);
            $max = $sender->getGroup()['maxHomes'];
            if (!$isHome && !$this->hasPermission($sender) . '.*' && $homeManager->countHomes() >= $max) {
                $sender->sendMessage('§4[!] §6Вам доступно только: §c' . $max . ' §6точек дома. §2Улучшите привилегию §6для доступа к большему количеству точек дома');
                return;
            }
            $homeManager->setHome($args[0], $sender->getPosition());
            $sender->sendMessage('§2[!] §7Точка дома §e' . $args[0] . '§7 установлена!');
            $sender->sendMessage('§2[!] §7Используйте §f/hm ' . $args[0] . ' §7для телепортации на точку');
            $sender->sendMessage('§2[!] §7Список ваших точек (' . $homeManager->countHomes() . ' шт.): §f' . implode('§7, §f', $homeManager->getHomeList()));
        } else {
            $sender->sendMessage($this->getConsoleUsage());
        }
    }
}