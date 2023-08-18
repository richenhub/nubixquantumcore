<?php 

namespace Richen\Commands\Player;

use Richen\Custom\NBXPlayer;

class fly extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Изменить режим полёта'); }
    private array $count = [];
    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if ($sender instanceof NBXPlayer) {
            $player = $sender;
            if (isset($args[0])) {
                if ($this->hasPermission($sender, '.other')) {
                    $exact = $this->getPlayerByName($args[0]);
                    if ($exact instanceof NBXPlayer) $player = $exact;
                } else {
                    $sender->sendMessage('§4[!] §cВы не можете изменять режим полёта другим игрокам');
                }
            }
            $flight = $player->getAllowFlight();
            $player->setAllowFlight(!$flight);
            if (isset($exact) && $exact !== $player) {
                $sender->sendMessage('§2[!] §7Режим полёта для игрока ' . $player->getName() . ' был ' . (!$flight ? '§aвключен' : '§cвыключен'));
            }
            $player->sendMessage('§2[!] §7Режим полёта был ' . (!$flight ? '§aвключен' : '§cвыключен'));
        } else {
            $sender->sendMessage($this->getConsoleUsage());
        }
    }
}