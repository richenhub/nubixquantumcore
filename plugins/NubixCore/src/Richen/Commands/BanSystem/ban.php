<?php 

namespace Richen\Commands\BanSystem;

use NBX\Manager\CommandManager;
use Richen\Engine\Utils;

class ban extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Блокировка игрока'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (count($args) < 3) return $sender->sendMessage($this->getUsageMessage('[игрок] [время] [причина]'));
        $sendername = mb_strtolower($sender->getName());
        $targetname = mb_strtolower($args[0]);
        $reason = implode(' ', array_slice($args, 2));
        $user = $this->core()->getServer()->getPlayerExact($targetname);
        $time = Utils::strtime2Sec($args[1]);
        $address = 0;
        $cid = 0;
        if (($pl = $this->getPlayerByName($targetname))) {
            $address = $pl->getAddress();
            $cid = $pl->getClientId();
        }
        if ($time > 0) {
            $newtime = time() + $time;
            $bs = $this->core()->bans();
            $res = $bs->setBan(1, $sendername, $targetname, $reason, $newtime, $address, $cid);
            $timeformat = Utils::sec2Time($time, false, false, true, true, true, false);
            if (is_numeric($res)) {
                $message = $this->lang()->prepare('ban', $this->lang()::WRN, [$targetname, $sendername, $timeformat, $reason]);
                $sender->getServer()->broadcastMessage($message);
                if ($user) {
                    $user->sendMessage('§6[!] §6На ваш аккаунт была наложена блокировка на §f' . $timeformat);
                    $user->sendMessage('§6[!] §6Доступ к игре на сервере Nubix.ru - §cограничен');
                    $user->sendMessage('§6[!] §6Разбан монжо купить на сайте §8http://§aNubix.ru');
                }
            } else {
                $sender->sendMessage($res);
            }
        } else {
            $sender->sendMessage('§4[!] §cВремя должно быть в формате: например, §62h = 2 часа, 25m = 25 минут');
            $sender->sendMessage('§6[!] §fИспользуйте: §e/ban [игрок] [время] [причина]');
        }
    }
}