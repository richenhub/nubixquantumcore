<?php 

namespace Richen\Commands;

use pocketmine\level\Position;
use pocketmine\Player;

class rg extends \Richen\NubixCmds {
    public array $submitcmd;

    public function __construct($name) {
        parent::__construct($name, 'Приват территорий');
    }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        $label = mb_strtolower($label);
        $username = mb_strtolower($sender->getName());
        $mn = $this->core()->rgns();

        if (isset($args[0])) {
            $subcmd = mb_strtolower($args[0]);
            array_shift($args);
        } else {
            $subcmd = 'help';
        }

        switch ($subcmd) {
            case 'help':
                $sender->sendMessage(
                    '§d●━━━━๑۩ §dПомощь по приватам §d۩๑━━━━●' . PHP_EOL .
                    '§d⫸ §eКак заприватить свою территорию?' . PHP_EOL .
                    '§7§l|§r §6/rg pos1§f: отметить первую точку привата' . PHP_EOL .
                    '§7§l|§r §6/rg pos2§f: отметить вторую точку привата' . PHP_EOL .
                    '§7§l|§r §6/rg claim [название]§f: сохранить выделенную территорию между точками ' . PHP_EOL .
                    '§d⫸ §eКак добавить друга в приват?' . PHP_EOL .
                    '§7§l|§r §6/rg addmem [приват] [ник]§f: Добавить друга в приват' . PHP_EOL .
                    '§7§l|§r §6/rg delmem [приват] [ник]§f: Удалить его из привата' . PHP_EOL .
                    '§d⫸ §eТакже стоит знать эти команды:' . PHP_EOL .
                    '§7§l|§r §6/rg me§f: посмотреть все свои приваты' . PHP_EOL .
                    '§7§l|§r §6/rg tp [приват]§f: телепортация на свой приват '. PHP_EOL .
                    '§7§l|§r §6/rg delete [приват]§f: удалить свой приват'
                );
                break;
            case 'pos1':
            case 'pos2':
                if ($sender instanceof Player) {
                    if (!$this->hasPermission($sender, '.' . $subcmd)) {
                        return $sender->sendMessage('§4[!] §cУ вас нет прав для установки точек');
                    }
                    $pos = $sender->getPosition();
                    $function = 'set' . ucfirst($subcmd);
                    $y = min(max($pos->getFloorY(), 0), 256);
                    $mn->$function($username, $pos->getFloorX(), $y, $pos->getFloorZ(), $pos->getLevel());
                    $sender->sendMessage('§5⫸ §fПозиция §e' . str_replace('pos', '', $subcmd) . ' §fустановлена в мире §2' . $pos->getLevel()->getFolderName() . ' §fна §6x: ' . $pos->getFloorX() . ', y: ' . $y . ', z: ' . $pos->getFloorZ());
                    $valid = $mn->isValidPos($username);
                    if (is_numeric($valid)) {
                        $sender->sendMessage('§e[!] §fВы §aуспешно §fустановили 2 точки, количество блоков: §2' . $valid);
                        $sender->sendMessage('§e[!] §fЧтобы §2заприватить §fвыбранную территорию используйте: §a/rg claim [название]');
                    } else {
                        $sender->sendMessage($valid);
                    }
                } else {
                    if (count($args) === 4) {
                        $world = $args[0];
                        $x = $args[1];
                        $y = $args[2];
                        $z = $args[3];
                        if (is_numeric($x) && is_numeric($y) && is_numeric($z)) {
                            $y = min(max($y, 0), 256);
                            if (($world = $this->core()->getServer()->getLevelByName($world)) !== null) {
                                $function = 'set' . ucfirst($subcmd);
                                $mn->$function($username, $x, $y, $z, $world);
                                $sender->sendMessage('Позиция ' . str_replace('pos', '', $subcmd) . ' установлена в мире ' . $world->getFolderName() . ' на x: ' . $x . ', y: ' . $y . ', z: ' . $z);
                                $valid = $mn->isValidPos($username);
                                if (is_numeric($valid)) {
                                    $sender->sendMessage('[!] Вы успешно установили 2 точки, количество блоков: ' . $valid);
                                    $sender->sendMessage('[!] Чтобы заприватить выбранную территорию используйте: /rg claim [название]');
                                } else {
                                    $sender->sendMessage($valid);
                                }
                            } else {
                                $sender->sendMessage('[!] Выбранный мир ' . $args[0] . ' не существует');
                            }
                        } else {
                            $sender->sendMessage('[!] Значения [x] [y] [z] должны быть числом');
                        }
                    } else {
                        $sender->sendMessage('[!] Используйте: /' . $label . ' ' . $subcmd . ' [мир] [x] [y] [z]');
                    }
                }
                break;
            
            case 'claim':
                if (!$this->hasPermission($sender, '.' . $subcmd)) {
                    return $sender->sendMessage('§4[!] §cУ вас нет прав для создания привата');
                }
                if (isset($args[0])) {
                    if ($mn->isValidName($args[0])) {
                        $valid = $mn->isValidPos($username);
                        if (is_numeric($valid)) {
                            $region = $mn->createRegion($args[0], $username);
                            $sender->sendMessage('§5⫸ §fВы успешно §2создали §fприват §a' . $region->getName());
                            $sender->sendMessage(
                                '§d⫸ §eКак добавить друга в приват?' . PHP_EOL .
                                '§7§l|§r §6/rg addmem [приват] [ник]§f: Добавить друга в приват' . PHP_EOL .
                                '§7§l|§r §6/rg delmem [приват] [ник]§f: Удалить его из привата'
                            );
                        } else {
                            $sender->sendMessage($valid);
                        }
                    } else {
                        $sender->sendMessage('§4[!] §cВ названии привата можно использовать только английские буквы и цифры');
                    }
                } else {
                    $sender->sendMessage('§4[!] §cИспользуйте: §6/' . $label . ' ' . $subcmd . ' [название] §cдля создания привата');
                }
                break;

            default:
                $sender->sendMessage('§6[!] §fИспользуйте: §e/' . $label . ' help§f, для помощи по привату территорий');
                break;

            case 'unclaim':
                if (!$this->hasPermission($sender, '.' . $subcmd)) {
                    return $sender->sendMessage('§4[!] §cУ вас нет прав для удаления привата');
                }
                if (isset($args[0])) {
                    $submit = false;
                    if (isset($this->submitcmd[$username]) && $this->submitcmd[$username] === $args[0]) {
                        $args[0] = $this->submitcmd[$username];
                        $region = explode('_', $args[0])[0];
                        $submit = true;
                    } else {
                        $region = $args[0];
                    }
                    $rg = $mn->getRegion($region);
                    if ($rg->isRegion()) {
                        if ($rg->getOwner() === $username) {
                            if ($submit) {
                                $mn->deleteRegion($rg);
                                $sender->sendMessage('§c⫸ §6Вы успешно §cудалили §6приват §c' . $region);
                                unset($this->submitcmd[$username]);
                            } else {
                                $submitcmd = $args[0] . '_' . (mt_rand(100,200));
                                $this->submitcmd[$username] = $submitcmd;
                                $sender->sendMessage('§6[!] §eЧтобы подтвердить удаление привата напишите §6/rg ' . $subcmd . ' ' . $submitcmd);
                            }
                        } else {
                            $sender->sendMessage('§4[!] §cДанный приват вам не принадлежит');
                        }
                    } else {
                        $sender->sendMessage('§4[!] §cПривата с названием §6' . $region . ' §cне существует');
                    }
                } else {
                    $sender->sendMessage('§6[!] §fИспользуйте: §e/' . $label . ' ' . $subcmd . ' [название] §fдля удаления вашего привата');
                }
                break;

            case 'list':
            case 'me':
                if (!$this->hasPermission($sender, '.' . $subcmd)) {
                    return $sender->sendMessage('§4[!] §cУ вас нет прав для просмотра списка приватов');
                }
                if (isset($args[0]) && $this->hasPermission($sender, '.' . $subcmd . '.other')) {
                    $regions = $mn->getUserRegions($args[0]);
                } else {
                    $regions = $mn->getUserRegions($username);
                }
                if (!empty($regions)) {
                    $sender->sendMessage('§6⫸ §fСписок приватов' . (isset($args[0]) ? '§e игрока ' . $args[0] : '') . ':');
                    $i = 1;
                    foreach ($mn->getUserRegions($username) as $region) {
                        if ($region instanceof \Richen\Custom\NBXRegion) {
                            $pos = $region->getCenterCoords();
                            $sender->sendMessage($i++ . ') ' . $region->getName() . ': x: ' . $pos['x'] . ', y: ' . $pos['y'] . ', z: ' . $pos['z']);
                        }
                    }
                } else {
                    $sender->sendMessage('§4[!] §6У ' . (isset($args[0]) ? 'игрока §e' . $args[0] : 'вас') . ' §6нет приватов');
                }
                break;

            case 'listall':
                if ($this->hasPermission($sender, '.' . $subcmd)) {
                    $page = isset($args[0]) && is_numeric($args[0]) && $args[0] > 0 ? $args[0] : 1;
                    $limit = 7;
                    $offset = ($page - 1) * $limit;
                    $data = $mn->getRegions();
                    $countAll = count($data);
                    $dataPage = array_slice($data, $offset, $limit);
                    $totalPages = ceil(count($data) / $limit);
                    if ($totalPages > $page) {
                        $page = $totalPages;
                    }
                    $sender->sendMessage('§6⫸ §fВсего приватов на сервере ' . $countAll . ':');
                    $i = $page === 1 ? 1 : $page * $limit - $limit + 1;
                    foreach ($dataPage as $item) {
                        $sender->sendMessage('§7' . $i++ . ') §e' . $item->getName() . ' §7- владелец: §6' . $item->getOwner());
                    }

                    $sender->sendMessage('§6⫸ §7Страница: §a' . $page . ' §7из §2' . $totalPages);
                } else {
                    $sender->sendMessage('§4[!] §cВы не можете смотреть список приватов сервера');
                }
                break;

            case 'info':
                if (!$this->hasPermission($sender, '.' . $subcmd)) {
                    return $sender->sendMessage('§4[!] §cУ вас нет прав для просмотра информации о приватах');
                }
                if (count($args) === 4) {
                    $world = $args[0];
                    $x = $args[1];
                    $y = $args[2];
                    $z = $args[3];
                    if (is_numeric($x) && is_numeric($y) && is_numeric($z)) {
                        $rg = $mn->getRegionByPos($x, $y, $z, $world);
                        if ($rg->isRegion()) {
                            $sender->sendMessage('§6[!] §7Найден приват на позиции: §e' . $x . ', ' . $y . ', ' . $z);
                            $this->sendInfo($sender, $rg);
                        } else {
                            $sender->sendMessage('§6[!] §7Приват на позиции §e' . $x . ', ' . $y . ', ' . $z . ' - не найден');
                        }
                    } else {
                        $sender->sendMessage('§6[!] §cКоординаты [x] [y] [z] должны быть числом');
                        $sender->sendMessage('§6[!] §fИспользуйте: §e/' . $label . ' ' . $subcmd . ' [мир] [x] [y] [z] §fдля поиска привата по позиции');
                    }
                } elseif (isset($args[0])) {
                    $rg = $mn->getRegion($args[0]);
                    if ($rg->isRegion()) {
                        $this->sendInfo($sender, $rg);
                    } else {
                        $sender->sendMessage('§4[!] §cПривата с названием §6' . $args[0] . ' §cне существует');
                    }
                } else {
                    if ($sender instanceof Player) {
                        $rg = $mn->getRegionByPos($sender->getPosition()->getFloorX(), $sender->getPosition()->getFloorY(), $sender->getPosition()->getFloorZ(), $sender->getLevel()->getName());
                        if ($rg->isRegion()) {
                            $sender->sendMessage('§6[!] §6Найден приват на месте где вы стоите');
                            $this->sendInfo($sender, $rg);
                        } else {
                            $sender->sendMessage('§4[!] §cПриват где вы стоите - не найден');
                        }
                    } else {
                        $sender->sendMessage('§6[!] §fИспользуйте §6/rg info §e[название] §fили §e[мир] [x] [y] [z]');
                    }
                }
                break;

            case 'flag':
                if (!$this->hasPermission($sender, '.' . $subcmd)) {
                    return $sender->sendMessage('§4[!] §cУ вас нет прав для использования флагов привата');
                }
                if (count($args) >= 2) {
                    $rg = $mn->getRegion($args[0]);
                    if ($rg->isRegion()) {
                        if ($rg->getOwner() === mb_strtolower($sender->getName())) {
                            $flagList = $mn->getAllFlags();
                            if (isset($flagList[$args[1]])) {
                                $flag = mb_strtolower($args[1]);
                                if ($this->hasPermission($sender, '.' . $subcmd . '.' . $flag)) {
                                    if (isset($args[2])) {
                                        switch ($args[2]) {
                                            case 'allow': case 'true': case 'on': case 'вкл': case '1': $value = true; break;
                                            case 'deny': case 'off': case 'false': case '0': case 'выкл': $value = false; break;
                                        }
                                    } else {
                                        $value = !$mn->getFlagStatus($rg, $flag);
                                    }
                                    $rg->setFlagStatus($flag, $value);
                                    $mn->setFlagStatus($rg, $flag, $value);
                                    if ($value) {
                                        $sender->sendMessage('§6[!] §fФлаг `§e'. $flag . '§f`: §2включен §fдля привата: '. $rg->getName() .'. ' . $flagList[$flag] . ' §aразрешено');
                                    } else {
                                        $sender->sendMessage('§6[!] §fФлаг `§e'. $flag . '§f`: §cвыключен §fдля привата: '. $rg->getName() .'. ' . $flagList[$flag] . ' §cзапрещено');
                                    }
                                } else {
                                    $sender->sendMessage('§4[!] §cУ вас нет прав для изменения флага §6' . $flag);
                                    $sender->sendMessage('§4[!] §6Улучшите привилегию для доступа к флагу §e' . $flag);
                                }
                            } else {
                                $sender->sendMessage('§4[!] §cФлага §6' . $args[1] . ' §cне существует');
                                $sender->sendMessage('§6[!] §6Список доступных вам для изменения флагов: ');
                                foreach ($flagList as $flag => $desc) {
                                    $sender->sendMessage('§6> `§e' . $flag . '§6` §f('.($this->hasPermission($sender, '.' . $subcmd . '.' . $flag) ? '§aдоступен' : '§cнет прав').'§f): §f' . $desc);
                                }
                            }
                        } else {
                            $sender->sendMessage('§4[!] §cПриват с названием §6' . $args[0] . ' §cвам не принадлежит');
                        }
                    } else {
                        $sender->sendMessage('§4[!] §cПривата с названием §6' . $args[0] . ' §cне существует');
                    }
                } else {
                    $sender->sendMessage('§6[!] §fИспользуйте: §e/rg flag [приват] [флаг] [значение]');
                    $sender->sendMessage('§6[!] §fЗначение - §aallow§7/§cdeny (§aвкл§7/§cвыкл)');
                }
                break;

            case 'delmem':
                if (!$this->hasPermission($sender, '.' . $subcmd)) {
                    return $sender->sendMessage('§4[!] §cУ вас нет прав для удаления участников из привата');
                }
                if (count($args) === 2) {
                    $region = $args[0];
                    $member = $args[1];
                    $rg = $mn->getRegion($region);
                    if ($rg->isRegion()) {
                        if ($rg->getOwner() === $username) {
                            if ($rg->getOwner() !== $member) {
                                if ($rg->isMember($args[1])) {
                                    $res = $mn->removeMember($rg, $member);
                                    if ($res) {
                                        $sender->sendMessage('§6[!] §eВы удалили игрока §6' . $member . ' §eиз вашего привата §2' . $rg->getName());
                                        if (($pl = $this->core()->getServer()->getPlayerExact($member)) !== null) {
                                            $pl->sendMessage('§6[!] §eВас удалили из привата §2' . $rg->getName() . ' §eигрока §a' . $username);
                                        }
                                    } else {
                                        $sender->sendMessage('§4[!] §cПри попытке выполнить команду произошла ошибка, попробуйте еще раз');
                                    }
                                } else {
                                    $sender->sendMessage('§4[!] §cИгрок §6'. $member .' не состоит в привате §6' . $rg->getName());
                                }
                            } else {
                                $sender->sendMessage('§4[!] §cВы не можете удалить себя из своего же привата');
                            }
                        } else {
                            $sender->sendMessage('§4[!] §cВы не являетесь владельцем привата §6' . $rg->getName());
                        }
                    } else {
                        $sender->sendMessage('§4[!] §cПривата с названием §6' . $args[0] . ' §cне существует');
                    }
                } else {
                    $sender->sendMessage('§6[!] §fИспользуйте: §e/rg delmem [приват] [ник] §fдля удаления участника из привата');
                }
                break;

            case 'addmem':
                if (!$this->hasPermission($sender, '.' . $subcmd)) {
                    return $sender->sendMessage('§4[!] §cУ вас нет прав для добавления участников в приват');
                }
                if (count($args) === 2) {
                    $region = $args[0];
                    $member = $args[1];
                    $rg = $mn->getRegion($region);
                    if ($rg->isRegion()) {
                        if ($rg->getOwner() === $username) {
                            if ($rg->getOwner() !== $member) {
                                if (!$rg->isMember($args[1])) {
                                    $res = $mn->addMember($rg->getId(), $member);
                                    if ($res) {
                                        $rg->addMember($member);
                                        $sender->sendMessage('§6[!] §eВы добавили игрока §6' . $member . ' §eв ваш приват §2' . $rg->getName());
                                        if (($pl = $this->core()->getServer()->getPlayerExact($member)) !== null) {
                                            $pl->sendMessage('§6[!] §eВас добавили в приват §2' . $rg->getName() . ' §eигрока §a' . $username);
                                        }
                                    } else {
                                        $sender->sendMessage('§4[!] §cПри попытке выполнить команду произошла ошибка, попробуйте еще раз');
                                    }
                                } else {
                                    $sender->sendMessage('§4[!] §cИгрок §6'. $member .' уже состоит в привате §6' . $rg->getName());
                                }
                            } else {
                                $sender->sendMessage('§4[!] §cВы не можете добавить себя в свой же приват');
                            }
                        } else {
                            $sender->sendMessage('§4[!] §cВы не являетесь владельцем привата §6' . $rg->getName());
                        }
                    } else {
                        $sender->sendMessage('§4[!] §cПривата с названием §6' . $args[0] . ' §cне существует');
                    }
                } else {
                    $sender->sendMessage('§6[!] §fИспользуйте: §e/rg addmem [приват] [ник] §fдля добавления участника в приват');
                }
                break;

            case 'sell':
                if (!$this->hasPermission($sender, '.' . $subcmd)) {
                    return $sender->sendMessage('§4[!] §cУ вас нет прав для продажи привата');
                }
                $max = 1000000;
                if (count($args) === 2) {
                    if (is_numeric($args[1]) && $args[1] >= 0 && $args[1] <= $max) {
                        $rg = $mn->getRegion($args[0]);
                        if ($rg->isRegion()) {
                            if ($rg->getOwner() === $username) {
                                if (!$rg->isSell() && $args[1] > 0) {
                                    $rg->updatePrice($args[1]);
                                    $mn->updatePrice($rg->getId(), $args[1]);
                                    $sender->sendMessage('§2[!] §eВы поставили свой приват §2' . $rg->getName() . ' §eна продажу за §a' . $args[1] . '$');
                                    $sender->sendMessage('§2[!] §eЧтобы снять приват с продажи установите цену §20$');
                                } else {
                                    $rg->updatePrice($args[1]);
                                    $mn->updatePrice($rg->getId(), $args[1]);
                                    if ($args[1] > 0) {
                                        $sender->sendMessage('§2[!] §eВы обновили цену продажи своего привата §2' . $rg->getName() . ' §eна §a' . $args[1] . '$');
                                    } else {
                                        $sender->sendMessage('§2[!] §eВы сняли свой приват §2' . $rg->getName() . ' §eс продажи');
                                    }
                                }
                            } else {
                                $sender->sendMessage('§4[!] §cВы не можете продать чужой приват');
                            }
                        } else {
                            $sender->sendMessage('§4[!] §cПривата с названием §6' . $args[0] . ' §cне существует');
                        }
                    } else {
                        $sender->sendMessage('§4[!] §cСумма должна быть числом не меньше §60 §cи не больше §6' . $max);
                    }
                } elseif (count($args) === 1 && $sender instanceof Player) {
                    if (is_numeric($args[0]) && $args[0] >= 0 && $args[0] <= $max) {
                        $rg = $mn->getRegionByPos($sender->getPosition()->getFloorX(), $sender->getPosition()->getFloorY(), $sender->getPosition()->getFloorZ(), $sender->getLevel()->getName());
                        if ($rg->isRegion()) {
                            if ($rg->getOwner() === $username) {
                                if (!$rg->isSell() && $args[0] > 0) {
                                    $rg->updatePrice($args[0]);
                                    $mn->updatePrice($rg->getId(), $args[0]);
                                    $sender->sendMessage('§2[!] §eВы поставили свой приват §2' . $rg->getName() . ' §eна продажу за §a' . $args[0] . '$');
                                    $sender->sendMessage('§2[!] §eЧтобы снять приват с продажи установите цену §20$');
                                } else {
                                    $rg->updatePrice($args[0]);
                                    $mn->updatePrice($rg->getId(), $args[0]);
                                    if ($args[0] > 0) {
                                        $sender->sendMessage('§2[!] §eВы обновили цену продажи своего привата §2' . $rg->getName() . ' §eна §a' . $args[0] . '$');
                                    } else {
                                        $sender->sendMessage('§2[!] §eВы сняли свой приват §2' . $rg->getName() . ' §eс продажи');
                                    }
                                }
                            } else {
                                $sender->sendMessage('§4[!] §cВы не можете продать чужой приват');
                            }
                        } else {
                            $sender->sendMessage('§4[!] §cПриват для продажи где вы стоите - не найден');
                        }
                    } else {
                        $sender->sendMessage('§4[!] §cСумма должна быть числом не меньше §60 §cи не больше §6' . $max);
                        $sender->sendMessage('§6[!] §fИспользуйте: §e/' . $label . ' [сумма $]§f, для продажи привата где стоите');
                        $sender->sendMessage('§6[!] §fИли используйте: §e/' . $label . ' [приват] [сумма $] §fдля продажи привата по его названию');
                    }
                } else {
                    $sender->sendMessage('§6[!] §fИспользуйте: §e/' . $label . ' sell [приват] [сумма $]');
                }
                break;

            case 'buy':
                if (!$this->hasPermission($sender, '.' . $subcmd)) {
                    return $sender->sendMessage('§4[!] §cУ вас нет прав для покупки приватов');
                }
                if (count($args) === 1) {
                    $rg = $mn->getRegion($args[0]);
                    if ($rg->isRegion()) {
                        if ($rg->isSell()) {
                            $usrmn = $this->core()->user();
                            $money      = $usrmn->getUserProp($username, 'money');
                            $moneyOwner = $usrmn->getUserProp($rg->getOwner(), 'money');
                            if ($rg->getOwner() !== $username) {
                                if ($money >= $rg->getPrice()) {
                                    $tax = (int)$rg->getPrice() * 0.05;
                                    $usrmn->setUserProp($username, ['money' => $money - $rg->getPrice()]);
                                    $usrmn->setUserProp($rg->getOwner(), ['money' => $moneyOwner + ($rg->getPrice() - $tax)]);
                                    $sender->sendMessage('§2[!] §eВы приобрели приват §6' . $rg->getName() . ' §eза §2' . $rg->getPrice() . '$');
                                    if (($pl = $this->core()->getServer()->getPlayerExact($rg->getOwner())) !== null) {
                                        $pl->sendMessage('§2[!] §fИгрок §6' . $username . ' §fкупил ваш приват §e' . $rg->getName() . ' §fза $2' . $rg->getPrice() . '$ §7(Комиссия: 5% §6-'.$tax.'$§7)');
                                    }
                                } else {
                                    $sender->sendMessage('§4[!] §cУ вас недостаточно средств для покупки привата: ' . $rg->getName() . ' §cстоимостью §2' . $rg->getPrice() . '$');
                                }
                            } else {
                                $sender->sendMessage('§4[!] §cВы не можете купить свой приват §6' . $rg->getName());
                            }
                        } else {
                            $sender->sendMessage('§4[!] §cПриват §6' . $rg->getName() . ' §c- не продается');
                        }
                    } else {
                        
                    }
                } elseif ($sender instanceof Player) {
                    $rg = $mn->getRegionByPos($sender->getPosition()->getFloorX(), $sender->getPosition()->getFloorY(), $sender->getPosition()->getFloorZ(), $sender->getLevel()->getName());
                    if ($rg->isRegion()) {
                        if ($rg->isSell()) {
                            if ($rg->getOwner() !== $username) {
                                $sender->sendMessage('§6[!] §fЧтобы купить приват где вы стоите за §2' . $rg->getPrice() . '$§f, напишите: §e/rg buy ' . $rg->getName());
                            } else {
                                $sender->sendMessage('§4[!] §cВы не можете купить свой приват §6' . $rg->getName());
                            }
                        } else {
                            $sender->sendMessage('§4[!] §cПриват §6' . $rg->getName() . ' §cгде вы стоите - не продается');
                        }
                    } else {
                        $sender->sendMessage('§4[!] §cПриват для покупки где вы стоите - не найден');
                    }
                } else {
                    $sender->sendMessage('§6[!] §fИспользуйте: §e/' . $label . ' [приват]');
                }

                break;

            case 'leave':
                if (!$this->hasPermission($sender, '.' . $subcmd)) {
                    return $sender->sendMessage('§4[!] §cУ вас нет прав для выхода из привата');
                }
                if (count($args) === 1) {
                    $region = $args[0];
                    $rg = $mn->getRegion($region);
                    if ($rg->isRegion()) {
                        if ($rg->isMember($username) && $rg->getOwner() !== $username) {
                            $res = $mn->removeMember($rg, $username);
                            if ($res) {
                                $sender->sendMessage('§6[!] §eВы покинули приват §2' . $rg->getName());
                            } else {
                                $sender->sendMessage('§4[!] §cПри попытке выполнить команду произошла ошибка, попробуйте еще раз');
                            }
                        } else {
                            if ($rg->getOwner() === $username) {
                                $sender->sendMessage('§4[!] §cВы не можете покинуть свой приват, но можете удалить его §6/rg delete [приват]');
                            } else {
                                $sender->sendMessage('§4[!] §cВы не состоите в привате §6' . $region);
                            }
                        }
                    } else {
                        $sender->sendMessage('§4[!] §cПриват с названием §6' . $region . ' §cне найден');
                    }
                } else {
                    $sender->sendMessage('§6[!] §fИспользуйте: /rg leave [приват], чтобы покинуть приват куда вас добавили');
                }
                break;

            case 'tp':
                if ($sender instanceof \Richen\Custom\NBXPlayer) {
                    if ($this->hasPermission($sender, '.' . $subcmd)) {
                        $regions = $this->hasPermission($sender, '.' . $subcmd . '.other') ? $mn->getRegions() : $mn->getUserRegions($username);
                        if (!isset($args[0]) || (isset($args[0]) && isset($regions[$args[0]]))) {
                            if (isset($args[0]) && isset($regions[$args[0]])) {
                                $region = $regions[$args[0]];
                            } elseif (isset($args[0]) && !isset($regions[$args[0]])) {
                                $sender->sendMessage('§4[!] §cПривата с названием §6' . $args[0] . ' §cне существует');
                            } else {
                                $region = $regions[array_rand($regions)];
                                $center = $region->getCenterCoords();
                                $position = new Position($center['x'], $center['y'], $center['z'], $region->getLevel());
                                //$sender->sendMessage('§6[!] §fТелепортация в приват случайный приват §e' . $region->getName());
                                $sender->teleportManager()->teleport($sender, $position, 'Телепортация в случайный приват через %s', '§dВы телепортировались в приват §e' . $region->getName());
                                return;
                            }
                            $center = $region->getCenterCoords();
                            $position = new Position($center['x'], $center['y'], $center['z'], $region->getLevel());
                            $sender->teleportManager()->teleport($sender, $position, '[!] Телепортация в приват ' . $region->getName() . ' через %s', '§dВы телепортировались в приват §e' . $region->getName());
                        } else {
                            $sender->sendMessage('§6[!] §fИспользуйте: §e/rg tp [приват]§f, или не вводите название привата, для телепортации в §dслучайный приват');
                        }
                    } else {
                        $sender->sendMessage('§4[!] §cВы не можете использовать телепорт в приват');
                        $sender->sendMessage('§4[!] §6Улучшите привилегию, для доступа к этой возможности');
                    }
                } else {
                    $sender->sendMessage('§4[!] §cКоманда /rg tp доступна только в игре');
                }
                break;
        }
    }

    public function sendInfo($sender, \Richen\Custom\NBXRegion $rg) {
        $pos = $rg->getCenterCoords();
        $flagList = $this->core()->rgns()->getAllFlags();
        $flags = [];
        foreach ($rg->getFlags() as $flag => $value) {
            $value = $rg->getFlag($flag);
            $flags[] = $flagList[$flag] . ' - ' . ($value ? '§aРазрешено' : '§cЗапрещено');
        }
        
        $sender->sendMessage(
            '§d●━━━━๑۩ §fИнформация о привате §e§l' . $rg->getName() . ': §r§d۩๑━━━━●' . PHP_EOL .
            '§d> §eВладелец: §6' . $rg->getOwner() . ' §e✶' . PHP_EOL .
            '§d> §eЦентр привата: §f' . 'x: ' . $pos['x'] . ', y: ' . $pos['y'] . ', z: ' . $pos['z'] . PHP_EOL .
            '§d> §eУчастники: §f' . (($members = implode(', ', $rg->getMembers())) === '' ? '§cнет участников' : $members) . PHP_EOL .
            '§d> §eРазрешения (флаги): §f' . implode(' §7| §f', $flags) . PHP_EOL .
            '§d> §eПриват ' . ($rg->isSell() ? '§fв продаже за §2' . $rg->getPrice() . '$' : '§6не продается')
        );
    }
}