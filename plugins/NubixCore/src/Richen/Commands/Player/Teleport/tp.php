<?php 

namespace Richen\Commands\Player\Teleport;

use Richen\Custom\NBXPlayer;
use pocketmine\level\Position;
use pocketmine\level\Level;

class tp extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Телепортация'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof NBXPlayer) return $sender->sendMessage($this->getConsoleUsage());
        $tpmn = $sender->teleportManager();
        if (count($args) === 1) {
            $pl = $this->core()->getServer()->getPlayerExact($args[0]);
            if ($pl instanceof NBXPlayer) {
                $tpmn->teleport($pl->getPosition(), 'Телепортация к игроку ' . $pl->getName() . ' %s', '§eВы телепортированы к игроку ' . $pl->getName());
            } else {
                return $sender->sendMessage($this->getOfflineMessage($args[0]));
            }
        } elseif (count($args) === 2) {
            $pl  = $this->core()->getServer()->getPlayerExact($args[0]);
            $pl2 = $this->core()->getServer()->getPlayerExact($args[1]);
            if ($pl instanceof NBXPlayer && $pl2 instanceof NBXPlayer) {
                $pl->teleportManager()->teleport($pl2->getPosition(), 'Телепортация к игроку ' . $pl2->getName() . ' %s', '§eВы телепортированы к игроку ' . $pl2->getName());
            } else {
                return $sender->sendMessage('§4[!] §cОдин из игроков не онлайн');
            }
        } elseif (count($args) === 5) {
            $pl = $this->core()->getServer()->getPlayerExact($args[0]);
            if ($pl instanceof NBXPlayer) {
                $world = $this->core()->getServer()->getLevelByName($args[4]);
                if ($world instanceof Level) {
                    if (is_numeric($args[1]) && is_numeric($args[2]) && is_numeric($args[3])) {
                        $pl->teleportManager()->teleport(new Position((int)$args[1], (int)$args[2], (int)$args[3], $world), 'Телепортация в выбранную точку %s', '§eВы телепортировались в выбранную точку' . PHP_EOL .'§ex: ' . $args[1] . ', y: ' . $args[2] . ', z: ' . $args[3] . ' §fв мире §6' . $args[4]);
                    } else {
                        $sender->sendMessage($this->getUsageMessage('[игрок] §6[x] [y] [z] [world]'));
                    }
                } else {
                    $sender->sendMessage('§4[!] §cМира с названием §6' . $args[4] . ' §cне существует');
                }
            } else {
                return $sender->sendMessage($this->getOfflineMessage($args[0]));
            }
        } else {
            $sender->sendMessage($this->getUsageMessage('[игрок] [место]'));
            $sender->sendMessage('§6[!] §fЕсли не указано место, телепортация к игроку');
            $sender->sendMessage('§6[!] §fМеста:');
            $sender->sendMessage($this->getUsageMessage('[игрок] [другой игрок]'));
            $sender->sendMessage($this->getUsageMessage('[игрок] §6[x] [y] [z] [world]'));
        }
    }
}