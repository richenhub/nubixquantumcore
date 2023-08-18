<?php 

namespace Richen\Commands;

class cases extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Кейсы', ['dc']); }
    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        $cases = $this->core()->case();
        switch (mb_strtolower($args[0] ?? 'help')) {
            case 'add':
                if (!$this->checkPermission($sender, 'add')) return;
                if (count($args) !== 3 || !is_numeric($args[2])) return $sender->sendMessage($this->getUsageMessage('add <игрок> <количество>'));
                $name = $args[1];
                $count = $args[2];
                $user_id = $this->core()->user()->getUserProp($name, 'id');
                if ($user_id === null) return $sender->sendMessage($this->lang()::ERR . ' §cИгрок не зарегистрирован');
                $cases->addCase($user_id, 0, $count);
                $sender->sendMessage($this->lang()::SUC . ' §fИгроку §2' . $name . ' §fбыло выдано §6' . $count . ' §fдонат кейсов');
                break;
            case 'open':
                $user_id = $this->core()->user()->getUserProp($sender instanceof \pocketmine\command\ConsoleCommandSender ? 'nubix' : $sender->getName(), 'id');
                $ucases = $cases->getCases($user_id);
                if (!count($ucases)) return $sender->sendMessage($this->lang()::ERR . ' §cУ вас нет кейсов.' . PHP_EOL . $this->lang()::INF . ' §eПриобрести кейсы можно на сайте: §bnubix.ru');
                $group = $cases->openCase($user_id, $ucases[0]['id']);
                $prefix = $this->core()->conf('groups')->config()->getAll()[$group]['prefix'];
                $sender->sendMessage($this->lang()::SUC . ' §fВы успешно открыли кейс! Вам выпала привилегия: §8(§e' . $prefix . '§8)');
                $this->serv()->broadcastMessage('§8[§dДонат§7-§fКейсы§8] §fИгрок §a' . $sender->getName() . ' §fоткрыл донат кейс и выбил привилегию: §8(§e' . $prefix . '§8)');
                
                break;
            case 'pay':
                if (count($args) !== 3 || !is_numeric($args[2])) return $sender->sendMessage($this->getUsageMessage('pay <игрок> <количество>'));
                $name = $args[1];
                $count = $args[2];
                $user_id = $this->core()->user()->getUserProp($sender instanceof \pocketmine\command\ConsoleCommandSender ? 'nubix' : $sender->getName(), 'id');
                $ucases = $cases->getCases($user_id);
                $user2_id = $this->core()->user()->getUserProp($name, 'id');
                if ($user2_id === null) return $sender->sendMessage($this->lang()::ERR . ' §cИгрок с ником §6' . $name . ' §cне зарегистрирован на сервере');
                if (!count($ucases)) return $sender->sendMessage($this->lang()::ERR . ' §cУ вас нет кейсов.' . PHP_EOL . $this->lang()::INF . ' §eПриобрести кейсы можно на сайте: §bnubix.ru');
                if (count($ucases) < $count) return $sender->sendMessage($this->lang()::ERR . ' §cВы не можете отправить больше §6' . count($ucases) . ' §cдонат кейсов');
                if ($cases->payCases($user_id, $user2_id, $count)) {
                    $sender->sendMessage($this->lang()::INF . ' §fВы передали игроку §6' . $name . ' §f- §e' . $count . ' шт. §fдонат кейсов');
                    if ($pl = $this->getPlayerByName($name)) {
                        $pl->sendMessage($this->lang()::INF . ' §fИгрок §6' . $sender->getName() . ' передал вам §e' . $count . ' шт. §fдонат кейсов');
                    }
                } else {
                    $sender->sendMessage($this->lang()::ERR . ' §cПроизошла ошибка во время передачи кейсов. Попробуйте ещё раз');
                }
                break;
            case 'info':
                $user_id = $this->core()->user()->getUserProp($sender instanceof \pocketmine\command\ConsoleCommandSender ? 'nubix' : $sender->getName(), 'id');
                $ucases = $cases->getAllCases($user_id);
                if (!count($ucases)) return $sender->sendMessage($this->lang()::ERR . ' §cВы не получали и не открывали кейсы' . PHP_EOL . $this->lang()::INF . ' §eПриобрести кейсы можно на сайте: §bnubix.ru');
                $opened = [];
                $closed = [];
                $maxPrice = 0;
                $group = null;

                foreach ($ucases as $case) {
                    if ((int)$case['result'] > 0) continue;
                    if ($case['status'] == 1) {
                        $closed[] = $case;
                    } else {
                        $opened[] = $case;
                        $price = $cases->getGroupsWithPrice()[$case['result']] ?? 0;
                        if ($price > $maxPrice) {
                            $maxPrice = $price;
                            $group = $case['result'];
                        }
                    }
                }
                $sender->sendMessage(
                    '§8[§dДонат§7-§fКейсы§8] §7Информация о ваших донат-кейсах:' . PHP_EOL .
                    '§8* §7У вас сейчас есть: §f' . count($closed) . ' кейсов§7. Открыть кейс: §d/cases open' . PHP_EOL . 
                    '§8* §7Всего открытых кейсов: §e' . count($opened) . ' шт.' . PHP_EOL . 
                    '§8* §7Самый дорогой выигрыш из донат-кейса: §6' . ($group !== null ? $group . ' §7- §a' . $maxPrice . ' р.' : '')
                );
                break;
            default:
                $sender->sendMessage('§8[§dДонат§7-§fКейсы§8] §fПомощь по донат-кейсам:');
                $sender->sendMessage('§7* §a/dc open §f- открыть донат кейс');
                $sender->sendMessage('§7* §a/dc info §f- информация о ваших кейсах');
                $sender->sendMessage('§7* §a/dc pay §f- передать кейсы');
                break;
        }
    }
}
