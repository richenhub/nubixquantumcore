<?php 

namespace Richen\Commands\Economy;

use Richen\Engine\Utils;
use Richen\NubixCmds;

class pay extends NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Отправить геймкоины игроку'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (count($args) < 2) return $sender->sendMessage($this->getUsageMessage('[игрок] [сумма]'));
        if (!is_numeric($message = Utils::isNumber(($value = $args[1]), 0))) return $sender->sendMessage($message);
        $name = $args[0];
        if (!is_numeric($value)) return $sender->sendMessage($this->lang()->prepare('money-error-no-numeric', $this->lang()::ERR));
        if (mb_strtolower($name) === mb_strtolower($sender->getName())) return $sender->sendMessage('§4[!] §cВы не можете отправить деньги самому себе');
        $cash = $this->core()->cash();
        $ucash = $cash->getUserCash($sender->getName());
        if (!$ucash->exists()) return $sender->sendMessage($this->lang()->prepare('money-error-not-exists', $this->lang()::ERR, [$sender->getName()]));
        $ucash2 = $cash->getUserCash($name);
        if (!$ucash2->exists()) return $sender->sendMessage($this->lang()->prepare('money-error-not-exists', $this->lang()::ERR, [$name]));

        $money = $ucash->getMoney();
        
        $tax = $value * 0.05;

        if ($money < $value + $tax) return $sender->sendMessage('§4[!] §cНедостаточно геймкоинов. §cВы пытаетесь отправить §e' . $value . '$ §6+ (' . $tax . '$ комиссия)§c. У вас есть: §a' . $money . '$ §2геймкоинов');

        $player = $this->serv()->getPlayerExact($name);

        $ucash->delMoney($value + $tax);
        $ucash2->addMoney($value);

        $tid = $this->core()->cash()::addTransaction($sender instanceof \Richen\Custom\NBXPlayer ? $sender->getId() : 0, $sender instanceof \pocketmine\Player ? $this->core()->cash()::TRNSTYPE_USER2USER : $this->core()->cash()::TRNSTYPE_SERV2USER, $this->core()->user()->getUserProp($name, 'id'), $value, $this->core()->cash()::MNS_ID, 'Перевод');
        $this->core()->cash()::addTransaction($sender instanceof \Richen\Custom\NBXPlayer ? $sender->getId() : 0, $this->core()->cash()::TRNSTYPE_USER_TAX, 0, $tax, $this->core()->cash()::MNS_ID, $tid);

        $sender->sendMessage('§6[!] §7Вы перевели игроку §e' . $name . ' §7- §a' . $this->core()->cash()->getCurrencyName($value, $this->core()->cash()::MNS_ID) . ' §6(' . $tax . '$ комиссия)');    
        if ($player !== null) {
            $player->sendMessage('§6[!] §7Игрок §e' . $sender->getName() . ' §7перевел вам §2' . $this->core()->cash()->getCurrencyName($value, $this->core()->cash()::MNS_ID) . ' §7и составляет теперь: §2' . $this->core()->cash()->getCurrencyName($ucash2->getMoney(), $this->core()->cash()::MNS_ID));
        }
    }
}