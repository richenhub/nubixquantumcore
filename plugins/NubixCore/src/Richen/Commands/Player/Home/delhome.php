<?php 

namespace Richen\Commands\Player\Home;

use Richen\Custom\NBXPlayer;
use Richen\NubixCmds;

class delhome extends NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Удаление точки дома'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (count($args) < 1) {
            return $sender->sendMessage($this->getUsageMessage('[название]'));
        }
        if ($sender instanceof NBXPlayer) {
            $homeManager = $sender->getHomeManager();
            $isHome = $homeManager->isHome($args[0]);
            if ($homeManager->countHomes() === 0) {
                $sender->sendMessage('§4[!] §cУ вас нет установленных точек дома');
                $sender->sendMessage('§4[!] §cДля установки точки используйте: §f/sethome [название]');
                return;
            }
            if (!$isHome) {
                $sender->sendMessage('§4[!] §cУ вас нет точки дома с названием: §6' . $args[0]);
                $sender->sendMessage('§4[!] §7Список ваших точек (' . $homeManager->countHomes() . '): §f' . implode('§7, §f', $homeManager->getHomeList()));
                return;
            }
            $homeManager->delHome($args[0]);
            $sender->sendMessage('§2[!] §7Точка дома §c' . $args[0] . '§7 удалена!');
            $sender->sendMessage('§2[!] §7Для установки новой точки используйте: §f/sethome [название]');
        } else {
            $sender->sendMessage($this->getConsoleUsage());
        }
    }
}