<?php 

namespace Richen\Commands\System;

class trans extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Транзакции'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!count($args)) return $sender->sendMessage($this->getUsageMessage('[тип] [страница]'));
        $transactions = $this->core()->data()->get('transactions', [['k' => 'transaction_type', 'v' => $args[0]]]);
        if (!count($transactions)) return $sender->sendMessage('§4[!] §cПусто');
        foreach ($transactions as $transaction) {
            $sender->sendMessage($this->core()->cash()->getTransactionName($transaction['transaction_type'], $transaction['sender_id'], $transaction['target_id']) . ' - ' . $this->core()->cash()->getCurrencyName($transaction['amount'], $transaction['amount_id'], true) . ': ' . $transaction['comment']);
        }
    }
}