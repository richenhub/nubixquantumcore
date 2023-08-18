<?php 

namespace Richen\Commands\Economy;

class money extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Баланс'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        $cash = $this->core()->cash();
        if (isset($args[0]) && $this->hasPermission($sender, 'other')) {
            $ucash = $cash->getUserCash($args[0]);
            if (!$ucash->exists()) return $sender->sendMessage($this->lang()->prepare('money-error-not-exists', $this->lang()::ERR, [$args[0]]));
            $sender->sendMessage($this->lang()->prepare('money-see-other', $this->lang()::INF, [$args[0]]));
        } else {
            $ucash = $cash->getUserCash($sender->getName());
            if (!$ucash->exists()) return $sender->sendMessage($this->lang()->prepare('money-error-need-register', $this->lang()::ERR));
            $sender->sendMessage($this->lang()->prepare('money-see', $this->lang()::INF));
        }
        $sender->sendMessage($cash->getCurrencyName($ucash->getMoney(), $cash::MNS_ID));
        $sender->sendMessage($cash->getCurrencyName($ucash->getNubix(), $cash::NBS_ID));
        $sender->sendMessage($cash->getCurrencyName($ucash->getBitcs(), $cash::BTC_ID));
        $sender->sendMessage($cash->getCurrencyName($ucash->getDebet(), $cash::DBT_ID));
    }
}