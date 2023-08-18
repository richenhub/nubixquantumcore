<?php 

namespace Richen\Commands\Player\Home;

use Richen\Custom\NBXPlayer;
use Richen\NubixCmds;

class home extends NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Точка дома'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if ($sender instanceof NBXPlayer) {
            $homeManager = $sender->getHomeManager();

            $countHomes = $homeManager->countHomes();

            if ($countHomes === 0) {
                $sender->sendMessage('§4[!] §cУ вас нет установленных точек дома');
                $sender->sendMessage('§4[!] §cДля установки точки используйте: §f/sethome [название]');
                return;
            }

            if ($homeManager->countHomes() > 1 && !count($args)) {
                $sender->sendMessage($this->getUsageMessage('[название]'));
                return;
            }

            if (count($args) && !$homeManager->isHome($args[0]) && $homeManager->countHomes() > 0) {
                $sender->sendMessage('§4[!] §cУ вас нет точки дома с названием: §6' . $args[0]);
                $sender->sendMessage('§4[!] §7Список ваших точек (' . $homeManager->countHomes() . ' шт.): §f' . implode('§7, §f', $homeManager->getHomeList()));
                return;
            }
            $home = $homeManager->getHome($args[0] ?? $homeManager->getHomeList()[0]);

            if ($home) {
                $sender->getLastPosManager()->setLastPosition($sender->getPosition());
                $sender->teleportManager()->teleport($home['position'], '§aТелепортация в точку дома §e' . $home['name'] . ' %s', '§aТелепортация в точку дома §e' . $home['name'] . ' §aпрошла успешно');
            }
        } else {
            $sender->sendMessage($this->getConsoleUsage());
        }
    }
}