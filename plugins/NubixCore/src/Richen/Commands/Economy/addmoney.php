<?php

namespace Richen\Commands\Economy;

class addmoney extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Баланс'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (count($args) === 3) {
            $name = $args[0];
            $val = $args[1];
            $type = $args[2];
            if (!is_numeric($val)) return $sender->sendMessage($this->lang()->prepare('money-error-no-numeric', $this->lang()::ERR));
            $cash = $this->core()->cash();
            switch ($type) {
                case $cash::MNS_ID:
                    break;
                case $cash::BTC_ID:
                    $sender->sendMessage('§cВыдача биткоинов временно недоступна');
                    return;
                case $cash::NBS_ID:
                    $sender->sendMessage('§cВыдача донат валюты временно недоступна');
                    return;
                case $cash::DBT_ID:
                    $sender->sendMessage('§cВыдача банковской валюты временно недоступна');
                    return;
                case $cash::CLS_ID:
                    $sender->sendMessage('§cВыдача клановой валюты временно недоступна');
                    return;
                default:
                    $sender->sendMessage($this->lang()->prepare('money-error-type-not-found', $this->lang()::ERR));
                    return;
            }
            $ucash = $cash->getUserCash($name);
            if (!$ucash->exists()) return $sender->sendMessage($this->lang()->prepare('money-error-not-exists', $this->lang()::ERR, [$name]));
            $ucash->addMoney($val);
            $sender->sendMessage($this->lang()->prepare('money-add', $this->lang()::SUC, [$name, $val]));
            if ($player = $this->getPlayerByName($name)) {
                $player->sendMessage($this->lang()->prepare('money-target-add', $this->lang()::SUC, [$val]));
            }
        } else {
            $sender->sendMessage($this->getUsageMessage('<игрок> <сумма> <тип> - выдать деньги игроку'));
            return;
        }
    }
}