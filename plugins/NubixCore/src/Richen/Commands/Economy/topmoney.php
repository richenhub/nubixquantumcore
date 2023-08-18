<?php 

namespace Richen\Commands\Economy;

use NBX\Manager\CommandManager;
use NBX\Utils\Values;
use Richen\NubixCash;

class topmoney extends \Richen\NubixCmds {

    public function __construct($name) {
        parent::__construct($name, 'Топ богатых игроков');
    }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        $result = $this->core()->data()->get('cash', [['k' => 'money', 'v' => 0, 'o' => 3]], [['key' => 'money', 'isd' => 1]], ['10']);
        if (empty($result)) return $sender->sendMessage('§4[!] §cНа сервере нет игроков имеющих геймкоины');
        $sender->sendMessage('§6[!] §fТоп §aбогатых §fигроков:');
        $x = 1;
        foreach ($result as $row) {
            $sender->sendMessage('§e' . $x++ . ') §f' . $this->core()->user()->getUserPropById($row['owner_id'], 'username') . ': ' . $this->core()->cash()->getCurrencyName($row['money'], $this->core()->cash()::MNS_ID));
        }
    }
}