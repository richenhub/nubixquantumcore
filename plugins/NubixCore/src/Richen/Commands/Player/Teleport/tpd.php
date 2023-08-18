<?php 

namespace Richen\Commands\Player\Teleport;

use Richen\Custom\NBXPlayer;

class tpd extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Отмена запроса на телепортацию'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof NBXPlayer) return $sender->sendMessage($this->getConsoleUsage());
        $tpmn = $sender->teleportManager();
        if ($tpmn->hasRequests() && $sender instanceof NBXPlayer) {
            foreach ($tpmn->getRequests() as $name => $time) {
                $pl = $this->core()->getServer()->getPlayerExact($name);
                if ($pl instanceof NBXPlayer) $pl->sendMessage('§6[!] §7Игрок §c' . $sender->getName() . ' §7отменил ваш запрос на телепортацию');
            }
            $tpmn->unsetRequests();
            $sender->sendMessage('§6[!] §7Вы отменили все запросы на телепортацию');
        } else {
            $sender->sendMessage('§4[!] §cУ вас нет входящих запросов на телепортацию');
        }
    }
}