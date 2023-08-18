<?php 

namespace Richen\Commands;

use NBX\Utils\Consts;
use NBX\Utils\Values;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;

class clan extends \Richen\NubixCmds {
    private array $deleteCommand = [];
    private array $invite = [];
    public function __construct($name) { parent::__construct($name, 'Кланы', ['c']); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        $subcmd = mb_strtolower($args[0] ?? 'undefined');
        $cm = $this->core()->clan();
        $price = 10000;
        switch ($subcmd) {
            case 'create':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (count($args) !== 2) return $sender->sendMessage($this->getUsageMessage($subcmd . ' [название]'));
                $clanname = mb_strtolower($args[1]);
                if ($cm->getUserClan($sender->getName()) !== null) return $sender->sendMessage('§4[!] §cВы уже состоите в клане');
                if ($cm->clanExists($clanname)) return $sender->sendMessage('§4[!] §cКлан с таким названием `§6' . $clanname . '§c` уже существует');
                if (!preg_match('/^[a-zA-Z0-9]{3,8}$/', $clanname)) return $sender->sendMessage('§4[!] §cВ название клана могут быть только английские буквы и цифры. Минимум 3, максимум 8 символов в названии');
                if ($sender instanceof \Richen\Custom\NBXPlayer) {
                    $cash = $this->core()->cash();
                    $ucash = $cash->getCash($sender->getId());
                    $money = $ucash->getMoney();
                    if ($money < $price) return $sender->sendMessage('§4[!] §cНедостаточно геймкоинов. §6Стоимость создания клана: §2' . $price . ' $');
                }
                $id = $cm->createClan($clanname, $sender->getName());
                if ($id > 0) {
                    if ($sender instanceof \Richen\Custom\NBXPlayer) $ucash->delMoney($price);
                    $tid = $this->core()->cash()::addTransaction($sender instanceof \Richen\Custom\NBXPlayer ? $sender->getId() : 0, $sender instanceof \Richen\Custom\NBXPlayer ? $this->core()->cash()::TRNSTYPE_USER2SERV : $this->core()->cash()::TRNSTYPE_USER2SERV, $this->core()->user()->getUserProp($sender->getName(), 'id'), $price, $this->core()->cash()::MNS_ID, 'Создание клана');
                    $sender->sendMessage('§2[!] §fВы успешно создали клан с названием: §7' . mb_strtoupper($clanname) . ' §fза §2' . $price . '$');
                    $this->serv()->broadcastMessage('§6[!] §eНа сервере появился новый клан §7' . mb_strtoupper($clanname) . '! §fВладелец: §e' . $sender->getName());
                } else {
                    $sender->sendMessage('§4[!] §cПроизошла ошибка при создании клана. Попробуйте ещё раз или обратитесь за помощью');
                }
                break;
            case 'delete':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (count($args) !== 2) return $sender->sendMessage($this->getUsageMessage($subcmd . ' [название]'));
                $clanname = mb_strtolower($args[1]);
                if (($clan = $cm->getUserClan($sender->getName())) === null) return $sender->sendMessage('§4[!] §cВы не состоите в клане');
                if (!$clan->isOwner($sender->getName())) return $sender->sendMessage('§4[!] §cКлан может удалить только владелец клана');
                if (!isset($this->deleteCommand[$sender->getName()])) {
                    if ($clan->getName() !== $clanname) return $sender->sendMessage('§4[!] §cВы не состоите в клане §6' . $clanname);
                    $this->deleteCommand[$sender->getName()] = mb_strtolower($clanname . mt_rand(100,300));
                    $sender->sendMessage('§6[!] §eДля подтверждения удаления клана используйте команду ниже:');
                    $sender->sendMessage($this->getUsageMessage($subcmd . ' ' . $this->deleteCommand[$sender->getName()]));
                    return;
                } else {
                    $cmd = $this->deleteCommand[$sender->getName()];
                    unset($this->deleteCommand[$sender->getName()]);
                    if ($cmd !== $clanname) return $sender->sendMessage('§4[!] §cНе удалось подтвердить удаление клана. Попробуйте ещё раз');
                    $this->serv()->broadcastMessage('§6[!] §eКлан ' . $clan->getNameTag() . ' §eбыл распущен');
                    $cm->deleteClan($clan->getId());
                }
                break;
            case 'info':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (!isset($args[1]) && ($clan = $cm->getUserClan($sender->getName())) === null) return $sender->sendMessage($this->getUsageMessage($subcmd . ' [название]'));
                if (!isset($clan) || !$clan) {
                    $clan = $cm->getClanByName($args[1]);
                    if (!$clan) return $sender->sendMessage('§4[!] §cКлана с названием §6' . $args[1] . ' §cне существует');
                }
                $countMembers = count($clan->getMembers());
                $countStaffs = count($clan->getStaffs());
                $sender->sendMessage(
                    '§3Ⓘ §fИнформация о клане: ' . $clan->getNameTag() . PHP_EOL . 
                    '§3↱ §fВладелец клана: §e' . $clan->getOwner() . ' §6♛' . PHP_EOL . 
                    '§3| §fУчастники §7('. $countMembers .'чел.)§f: §7' . ($countMembers ? implode(', ', array_keys($clan->getMembers())) : '§6Нет участников') . PHP_EOL . 
                    '§3| §fМодераторы §7('. $countStaffs .'чел.)§f: §2' . ($countStaffs ? implode(', ', array_keys($clan->getStaffs())) : '§6Нет модераторов') . PHP_EOL . 
                    '§3| §fВ банке клана: ' . $this->core()->cash()->getCurrencyName($clan->getCash()->getMoney(), $this->core()->cash()::CLS_ID) . PHP_EOL . 
                    '§3| §fУровень §b' . $clan->getLVL() . ' ур. §7/ §fОчков опыта: §e' . $cm->getExpStats($clan->getLVL()+1, $clan->getExp()) . PHP_EOL . 
                    '§3| §fКланом наиграно: §3' . \Richen\Engine\Utils::sec2Time($clan->getOnline(), false, false)
                );
                break;
            case 'invite':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (count($args) !== 2) return $sender->sendMessage($this->getUsageMessage($subcmd . ' [игрок]'));
                $nick = mb_strtolower($args[1]);
                if (($clan = $cm->getUserClan($sender->getName())) === null) return $sender->sendMessage('§4[!] §cВы не состоите в клане');
                if (!$clan->isOwner($sender->getName()) && !$clan->isStuff($sender->getName())) return $sender->sendMessage('§4[!] §cВаш статус §7' . $cm::RANKS[$cm::MEMBER] . ' §cв клане не позволяет приглашать игроков в клан');
                if (!$pl = $this->serv()->getPlayerExact($nick)) return $sender->sendMessage($this->getOfflineMessage($nick));
                if (($clan2 = $cm->getUserClan($nick)) !== null) return $sender->sendMessage('§4[!] §cИгрок уже состоит в клане §6' . $clan2->getName());
                $this->invite[$pl->getName()] = $clan->getId();
                $pl->sendMessage('§e[!] §fВас пригласили в клан: ' . $clan->getNameTag());
                $pl->sendMessage('§e[!] §fИспользуйте: §e/c accept§f, чтобы §aпринять §fприглашение');
                $pl->sendMessage('§e[!] §fВы можете §7не принимать §fзапрос, просто §7проигнорировав §fэто сообщение');
                $sender->sendMessage('§e[!] §fВы отправили приглашение в ваш клан игроку: §a' . $pl->getName());
                break;
            case 'accept':
                if (!isset($this->invite[$sender->getName()])) return $sender->sendMessage('§4[!] §cВам не поступало приглашение в клан');
                if (($clan = $cm->getUserClan($sender->getName())) !== null) return $sender->sendMessage('§4[!] §cВы уже состоите в клане');
                $clan = $cm->getClanById($this->invite[$sender->getName()]);
                unset($this->invite[$sender->getName()]);
                if (!$clan) return $sender->sendMessage('§4[!] §cПриглашение больше не действительно');
                $cm->addMember($clan, $sender->getName());
                $cm->broadcastMessage($clan, '§fПоприветствуйте нового участника клана: ' . $sender->getName());
                break;
            case 'list':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                $clans = $cm->getAllClans();
                if (empty($clans)) return $sender->sendMessage('§4[!] §cНа сервере нет кланов');
                $page = max(1, min(isset($args[1]) && is_numeric($args[1]) ? intval($args[1]) : 1, ceil(count($clans) / 10)));
                $dataPage = array_slice($clans, ($page - 1) * 10, 10);
                $sender->sendMessage('§6[!] §7На сервере §6' . count($clans) . '§7 кланов. Показана страница §e' . $page . '§7:');
                foreach ($dataPage as $i => $data) {
                    $clan = $cm->prepareClan($data);
                    if ($clan) {
                        $sender->sendMessage('§7' . (($page - 1) * 10 + $i + 1) . ') §e' . $clan->getNameTag() . ' - ' . $clan->getLVL() . ' ур.');
                    }
                }
                break;
            case 'home':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (!$sender instanceof \Richen\Custom\NBXPlayer) return $sender->sendMessage('§4[!] §cКоманда доступна только в игре');
                if (($clan = $cm->getUserClan($sender->getName())) === null) return $sender->sendMessage('§4[!] §cВы не состоите в клане');
                $home = $clan->getHome();
                if (!$home instanceof \pocketmine\level\Position) return $sender->sendMessage('§4[!] §cТочка клана не установлена');
                $sender->teleportManager()->teleport($home, 'Телепортация в точку клана %s', '§2[!] §fВы §aтелепортировались §fв точку клана');
                break;
            case 'sethome':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (!$sender instanceof \Richen\Custom\NBXPlayer) return $sender->sendMessage('§4[!] §cТочку клана можно установить только в игре');
                if (($clan = $cm->getUserClan($sender->getName())) === null) return $sender->sendMessage('§4[!] §cВы не состоите в клане');
                if (!$clan->isOwner($sender->getName()) && !$clan->isStuff($sender->getName())) return $sender->sendMessage('§4[!] §cВаш статус §7' . $cm::RANKS[$cm::MEMBER] . ' §cне позволяет вам устанавливать точку дома клана');
                $cm->setHome($clan, $sender->getPosition());
                $sender->sendMessage('§2[!] §7Точка клана установлена');
                break;
            case 'help':
                $sender->sendMessage(
                    '§d●━━━━๑۩ §dПомощь по кланам §d۩๑━━━━●' . PHP_EOL .
                    '§d⫸ §eКак создать клан?' . PHP_EOL .
                    '§7§l|§r §6/c create [название]§f: создать клан стоит ' . $this->core()->cash()->getCurrencyName(10000, $this->core()->cash()::MNS_ID) . PHP_EOL .
                    '§7§l|§r §6/c delete [название]§f: чтобы удалить клан' . PHP_EOL .
                    '§d⫸ §eКак добавить игрока в клан?' . PHP_EOL .
                    '§7§l|§r §6/c invite [ник]§f: Чтобы пригласить игрока в клан' . PHP_EOL .
                    '§7§l|§r §6/c kick [ник]§f: Испключить игрока из клана' . PHP_EOL .
                    '§d⫸ §eТакже стоит знать эти команды:' . PHP_EOL .
                    '§7§l|§r §6/c color§f: Изменить цвет клана' . PHP_EOL .
                    '§7§l|§r §6/c home§f: телепортация в клановый дом'. PHP_EOL .
                    '§7§l|§r §6/c bonus§f: получить клановый бонус' . PHP_EOL .
                    '§7§l|§r §6/c promote/demote§f: повышение и понижение модератора клана'
                );
                break;
            case 'kick':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (($clan = $cm->getUserClan($sender->getName())) === null) return $sender->sendMessage('§4[!] §cВы не состоите в клане');
                if (count($args) !== 2) return $sender->sendMessage($this->getUsageMessage($subcmd . ' [игрок]'));
                $nick = mb_strtolower($args[1]);
                if (!$clan->isOwner($sender->getName()) && !$clan->isStuff($sender->getName())) return $sender->sendMessage('§4[!] §cВаш статус §7' . $cm::RANKS[$cm::MEMBER] . ' §cне позволяет вам исключать игроков из клана');
                if ($clan->isOwner($args[1])) return $sender->sendMessage('§4[!] §cВы не можете исключить лидера клана');
                if (!$clan->isMember($args[1])) return $sender->sendMessage('§4[!] §cИгрок с ником §6' . $args[1] . ' §cне состоит в клане');
                if (!$cm->kick($clan, $nick)) return $sender->sendMessage('§4[!] §cПроизошла ошибка во время исключения игрока из клана, попробуйте ещё раз');
                $cm->broadcastMessage($clan, '§7Игрок §c' . $nick . ' §7был исключен из клана');
                if (($pl = $this->serv()->getPlayerExact($nick)) !== null) {
                    $pl->sendMessage('§6[!] §eВы были исключены из клана ' . $clan->getNameTag());
                }
                break;
            case 'leave':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (($clan = $cm->getUserClan($sender->getName())) === null) return $sender->sendMessage('§4[!] §cВы не состоите в клане');
                if ($clan->isOwner($sender->getName())) return $sender->sendMessage('§4[!] §cВы не можете покинуть свой клан, но можете распустить §6/c delete [название]');
                if (!$cm->kick($clan, $sender->getName())) return $sender->sendMessage('§4[!] §cПроизошла ошибка во время исключения игрока из клана, попробуйте ещё раз');
                $cm->broadcastMessage($clan, '§7Игрок §c' . $sender->getName() . ' §7покинул клан');
                $sender->sendMessage('§6[!] §eВы покинули клан ' . $clan->getNameTag());
                break;
            case 'up':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (($clan = $cm->getUserClan($sender->getName())) === null) return $sender->sendMessage('§4[!] §cВы не состоите в клане');
                if (!$clan->isOwner($sender->getName()) && !$clan->isStuff($sender->getName())) return $sender->sendMessage('§4[!] §cВаш статус §7' . $cm::RANKS[$cm::MEMBER] . ' §cне позволяет вам устанавливать точку дома клана');
                if ($cm->needExp($clan->getLVL()+1) > ($exp = $clan->getExp())) return $sender->sendMessage('§4[!] §cНедостаточно очков опыта для улучшения клана §7('.$cm->getExpStats($clan->getLVL()+1, $exp).'§7)');
                $cm->setClanProp($clan->getName(), ['lvl' => $clan->getLVL()+1, 'exp' => $exp - $cm->needExp($clan->getLVL()+1)]);
                $clan = $cm->getClanById($clan->getId());
                $cm->broadcastMessage($clan, '§eКлан был улучшен до §f' . $clan->getLVL() . ' §eуровня!');
                break;
            case 'color':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (($clan = $cm->getUserClan($sender->getName())) === null) return $sender->sendMessage('§4[!] §cВы не состоите в клане');
                if (!$clan->isOwner($sender->getName()) && !$clan->isStuff($sender->getName())) return $sender->sendMessage('§4[!] §cВаш статус §7' . $cm::RANKS[$cm::MEMBER] . ' §cне позволяет вам менять цвет клана');
                if ($clan->getLVL() < 3) return $sender->sendMessage('§4[!] §6Чтобы менять цвет названия клана, клан должен быть §e3 уровня');
                $colors = \Richen\Engine\Consts::getClanColors();
                $help_colors = [];
                foreach ($colors as $key => $color) $help_colors[] = $color . $key;
                $help_colors[] = '§f10 §c§6ц§eв§aе§bт§3н§dо§cй';
                if (!isset($args[1])) return $sender->sendMessage($this->getUsageMessage($subcmd . ' §8- §7Доступные цвета: ' . implode(' ', $help_colors)));
                if (!in_array($args[1], array_keys($colors)) && (int)$args[1] !== 10) return $sender->sendMessage('§4[!] §cНеизвестный идентификатор цвета' . PHP_EOL . '§4[!] §cДоступные цвета: ' . implode(' ', $help_colors));
                if ($args[1] === 10 && $clan->getLVL() < 7) return $sender->sendMessage('§4[!] §6Чтобы использовать §4Ц§св§6е§eт§3н§bо§dй §6формат названия, клан должен быть §e7 уровня');
                $oldtag = $clan->getNameTag();
                $cm->setClanProp($clan->getName(), ['color' => $args[1]]);
                $clan->setColor($args[1]);
                $sender->sendMessage('§2[!] §7Цвет клана §aобновлен §7с ' . $oldtag . ' §7на ' . $clan->getNameTag());
                break;
            case 'promote':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (($clan = $cm->getUserClan($sender->getName())) === null) return $sender->sendMessage('§4[!] §cВы не состоите в клане');
                if (!$clan->isOwner($sender->getName())) return $sender->sendMessage('§4[!] §cВаш статус не позволяет вам управлять модераторами');
                if (count($args) !== 2) return $sender->sendMessage($this->getUsageMessage($subcmd . ' [игрок]'));
                if (!($target = $this->getPlayerByName($name = mb_strtolower($args[1]))) instanceof \Richen\Custom\NBXPlayer) return $sender->sendMessage(is_string($target) ? $target : $this->getOfflineMessage($name));
                $name = $target->getName();
                if (!$clan->isMember($name)) return $sender->sendMessage('§4[!] §cИгрок §6' . $name . ' §cне состоит в вашем клане');
                if ($clan->isOwner($name)) return $sender->sendMessage('§4[!] §cНельзя повысить в должности Владельца клана');
                if ($clan->isStuff($name)) return $sender->sendMessage('§4[!] §cИгрок уже является Модератором клана, его нельзя повысить');
                if ($cm->promote($clan, $name)) {
                    $cm->broadcastMessage($clan, '§fИгрок §e' . $name . ' §fбыл повышен до §2Модератора §fклана');
                } else {
                    return $sender->sendMessage('§4[!] §cПроизошла ошибка во время выполнения команды, попробуйте ещё раз');
                }
                break;
            case 'online':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (($clan = $cm->getUserClan($sender->getName())) === null) return $sender->sendMessage('§4[!] §cВы не состоите в клане');
                $online = [];
                foreach ($cm->getOnlinePlayers($clan) as $player) {
                    $name = $player->getName();
                    $prefix = '§7';
                    if ($clan->isStuff($name)) $prefix = '§2♖ §a';
                    if ($clan->isOwner($name)) $prefix = '§6♛ §e';
                    $online[] = $prefix . $player->getName();
                }
                $sender->sendMessage('§6[!] §fВ вашем клане сейчас онлайн на сервере: ' . implode('§f, ', $online));
                break;
            case 'demote':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (($clan = $cm->getUserClan($sender->getName())) === null) return $sender->sendMessage('§4[!] §cВы не состоите в клане');
                if (!$clan->isOwner($sender->getName())) return $sender->sendMessage('§4[!] §cВаш статус не позволяет вам управлять модераторами');
                if (count($args) !== 2) return $sender->sendMessage($this->getUsageMessage($subcmd . ' [игрок]'));
                $name = mb_strtolower($args[1]);
                if (!$clan->isMember($name)) return $sender->sendMessage('§4[!] §cИгрок §6' . $name . ' §cне состоит в вашем клане');
                if ($clan->isOwner($name)) return $sender->sendMessage('§4[!] §cНельзя понизить в должности Владельца клана');
                if (!$clan->isStuff($name)) return $sender->sendMessage('§4[!] §cИгрок уже является Участником клана, его нельзя понизить');
                if ($cm->demote($clan, $name)) {
                    $cm->broadcastMessage($clan, '§fИгрок §e' . $name . ' §fбыл понижен до §7Участника §fклана');
                } else {
                    return $sender->sendMessage('§4[!] §cПроизошла ошибка во время выполнения команды, попробуйте ещё раз');
                }
                break;
            case 'addbank':
                if (!$this->checkPermission($sender, '.' . $subcmd)) return;
                if (!$sender instanceof \Richen\Custom\NBXPlayer && !$sender instanceof \pocketmine\command\ConsoleCommandSender) return $sender->sendMessage($this->getErrorMessage());
                if (($clan = $cm->getUserClan($sender->getName())) === null) return $sender->sendMessage('§4[!] §cВы не состоите в клане');
                if (count($args) !== 2) return $sender->sendMessage($this->getUsageMessage($subcmd . ' [сумма]'));
                if (!is_numeric($value = \Richen\Engine\Utils::isNumber($args[1], 0))) return $sender->sendMessage($value);
                $value = $args[1];
                $cash = $this->core()->cash();
                $ucash = $cash->getUserCash($sender->getName());
                if (!$ucash->exists()) return $sender->sendMessage($this->lang()->prepare('money-error-not-exists', $this->lang()::ERR, [$sender->getName()]));
                $money = $ucash->getMoney();
                $tax = $value * 0.05;
                if ($money < $value + $tax) return $sender->sendMessage('§4[!] §cНедостаточно геймкоинов. §cВы пытаетесь отправить в клан §e' . $value . '$ §6+ (' . $tax . '$ комиссия)§c. У вас есть: §a' . $money . '$ §2геймкоинов');
                $ccash = $cash->getCash(($clan->getId() + 2000000000) * -1);
                if (!$ccash->exists()) return $sender->sendMessage($this->lang()->prepare('money-error-not-exists', $this->lang()::ERR, [$clan->getName()]));
                $ucash->delMoney($value + $tax);
                $ccash->addMoney($value);
                $cm->broadcastMessage($clan, '§fИгрок §e' . $sender->getName() . ' §fпожертвовал ' . $this->core()->cash()->getCurrencyName($value, $this->core()->cash()::MNS_ID) . ' §fв клан', $sender->getName());
                if ($sender instanceof \Richen\Custom\NBXPlayer) {
                    $user_id = $sender->getUserId();
                }
                $tid = $this->core()->cash()->addTransaction($user_id ?? 0, $sender instanceof \Richen\Custom\NBXPlayer ? $this->core()->cash()::TRNSTYPE_USER2CLAN : $this->core()->cash()::TRNSTYPE_SERV2CLAN, ($clan->getId() + 2000000000)*-1, $value, $this->core()->cash()::MNS_ID, 'Клан: ' . $clan->getName());
                $this->core()->cash()->addTransaction($user_id ?? 0, $this->core()->cash()::TRNSTYPE_USER_TAX, 0, $tax, $this->core()->cash()::MNS_ID, $tid);
                break;
            default:
                $sender->sendMessage($this->getUsageMessage('help§f, для помощи по кланам'));
                break;
        }
    }
}
