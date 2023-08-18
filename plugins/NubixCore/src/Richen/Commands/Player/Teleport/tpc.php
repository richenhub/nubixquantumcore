<?php 

namespace Richen\Commands\Player\Teleport;

use Richen\Custom\NBXPlayer;

class tpc extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Принять запрос на телепортацию'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof NBXPlayer) return $sender->sendMessage($this->getConsoleUsage());
        $tpmn = $sender->teleportManager();
        if ($tpmn->hasRequests()) {
            $reqs = $tpmn->getRequests();
            foreach ($reqs as $name => $req) {
                $pl = $this->core()->getServer()->getPlayerExact($name);
                if ($pl instanceof NBXPlayer) {
                    $pl->sendMessage('§6[!] §7Игрок §e' . $pl->getName() . ' §7принял ваш запрос на телепортацию к нему');
                    $pl->teleportManager()->teleport($sender->getPosition(), 'Телепортация к игроку ' . $sender->getName() . ' через %s', '§fВы телепортировались к игроку §e' . $sender->getName());
                } else {
                    //$sender->sendMessage('§6[!] §7Игрок §c' . $name . ' §6отправивший вам запрос на телепортацию §6не онлайн');
                    unset($reqs[$name]);
                }
            }
            if (empty($reqs)) return $sender->sendMessage('§4[!] §cНет игроков онлайн, отправивших вам запрос на телепортацию');
            $tpmn->unsetRequests();
            $sender->sendMessage('§2[!] §7Игроки §f' . implode(', ', array_keys($reqs)) . ' §7были телепортированы к вам');
        } else {
            $sender->sendMessage('§4[!] §cУ вас нет входящих запросов на телепортацию');
        }
    }
}