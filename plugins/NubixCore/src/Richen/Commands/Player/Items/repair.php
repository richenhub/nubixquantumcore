<?php 

namespace Richen\Commands\Player\Items;

class repair extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Починить предмет в руке'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof \Richen\Custom\NBXPlayer) return $sender->sendMessage($this->getConsoleUsage());
        $item = $sender->getInventory()->getItemInHand();
        if ((!$item->isTool() && !$item->isArmor()) || $item->isNull()) return $sender->sendMessage($this->lang()::ERR . ' §cЭтот предмет починить нельзя');
        if (!$this->countdown($sender, 30)) return;
        $sub = mb_strtolower($args[0]);
        if ($sub === 'all' && $this->hasPermission($sender, $sub)) {
            $newContents = [];
            foreach ($sender->getInventory()->getContents() as $item) {
                if (($item->isTool() || $item->isArmor()) && !$item->isNull()) $item->setDamage(0);
                $newContents[] = $item;
            }
            $sender->getInventory()->setContents($newContents);
            $sender->sendMessage($this->lang()::SUC . ' §aВы починили все предметы в вашем инвентаре');
            return;
        }
        $item->setDamage(0);
        $sender->getInventory()->setItemInHand($item);
        $sender->sendMessage($this->lang()::SUC . ' §aВы починили предмет в вашей руке');
    }
}