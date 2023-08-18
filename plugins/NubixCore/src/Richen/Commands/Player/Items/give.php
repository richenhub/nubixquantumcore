<?php 

namespace Richen\Commands\Player\Items;

class give extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Выдача предметов'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (count($args) !== 3) return $sender->sendMessage($this->getUsageMessage('[игрок] [предмет] [количество]'));
        if (!($player = $this->getPlayerByName($args[0]))) return $sender->sendMessage($this->getOfflineMessage($args[0]));
        $argData = explode(':', $args[1]);
        $id = $argData[0];
        $dmg = $argData[1] ?? 0;
        $cnt = $args[2];
        foreach ([$id, $dmg, $cnt] as $val) {
            if (!is_numeric($val)) {
                return $sender->sendMessage($this->lang()::ERR . ' §cАйди предмета и количество должны быть числом, например /give ' . $sender->getName() . ' 264:0 5');
            }
        }
        $item = \pocketmine\item\Item::get($id, $dmg);
        $item->setCount($cnt);
        if ($item->getId() === \pocketmine\item\Item::AIR) return $sender->sendMessage($this->lang()::ERR . ' §cПредмета §6' . $id . ':' . $dmg . ' §cне существует');
        if (!$player->getInventory()->canAddItem($item)) return $sender->sendMessage($this->lang()::ERR . ' §cВ инвентаре игрока ' . $player->getName() . ' недостаточно места');
        $sender->sendMessage($this->lang()::SUC . ' §aВы выдали игроку §e' . $player->getName() . ' §7- §f' . $item->getName() . ' §7(' . $cnt . ' шт.)');
        $player->sendMessage($this->lang()::SUC . ' §aВИгрок §e' . $player->getName() . ' §aвыдал вам §7- §f' . $item->getName() . ' §7(' . $cnt . ' шт.)');
    }
}