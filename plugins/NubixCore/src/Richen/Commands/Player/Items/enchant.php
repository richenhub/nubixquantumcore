<?php 

namespace Richen\Commands\Player\Items;
use Richen\Custom\NBXPlayer;

class enchant extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Чарование'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof NBXPlayer) return;
        
        $inventory = $sender->getInventory();
        
        $item = \pocketmine\item\Item::get(61, 0, 1)->setCustomName("§0§dСупер-печка");
        
        $item->addEnchantment(\pocketmine\item\enchantment\Enchantment::getEnchantment(15)->setLevel(5));
        
        $inventory->addItem($item);
        
        $item = \pocketmine\item\Item::get(61, 0, 1)->setCustomName("§1§dСупер-печка");
        
        $item->addEnchantment(\pocketmine\item\enchantment\Enchantment::getEnchantment(18)->setLevel(3));
        
        $item->addEnchantment(\pocketmine\item\enchantment\Enchantment::getEnchantment(15)->setLevel(3));
        
        $inventory->addItem($item);
                
        $sender->sendMessage("§b▶ §fВам выданы все виды §eсупер-печек");
    }
}