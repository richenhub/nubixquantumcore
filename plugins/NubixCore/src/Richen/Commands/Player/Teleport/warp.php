<?php 

namespace Richen\Commands\Player\Teleport;

use NBX\Utils\Values;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\player\Player;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use Richen\Custom\NBXPlayer;
use Richen\Engine\Utils;

class warp extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Варпы'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof NBXPlayer) return $this->getConsoleUsage();
        if ($sender->teleportManager()->isTeleport()) return;
        if (count($args) === 2 && $this->hasPermission($sender, 'set')) {
            if ($args[0] === 'set') {
                if (($item = $sender->getInventory()->getItemInHand())->getId() === 0) {
                    return $sender->sendMessage('Необходимо взять в руку предмет');
                }
                if (mb_strlen($args[1]) >= 2 && mb_strlen($args[1]) <= 10) {
                    $serverData = $this->core()->getServerData();
                    $warps = $serverData['warps'] ?? [];
                    $warp = [
                        'name' => $args[1],
                        'item' => [
                            'id' => $item->getId(),
                            'damage' => $item->getDamage(),
                            'name' => '§eВарп: §f' . mb_strtoupper($args[1]),
                        ],
                        'position' => $sender->getPosition()->__toString()
                    ];
                    $warps[mb_strtolower($args[1])] = $warp;
                    $serverData['warps'] = $warps;
                    $this->core()->setServerData($serverData);
                    $sender->sendMessage('Точка варп ' . $args[1] . ' установлена');
                } else {
                    $sender->sendMessage('Название варпа должно быть не меньше 2 и не больше 10 символов');
                }
                return;
            }
            elseif ($args[0] === 'del') {
                if(isset($args[1])) {$serverData = $this->core()->getServerData();
                    $warps = $serverData['warps'] ?? [];
                    if (isset($warps[$args[1]])) {
                        unset($warps[$args[1]]);
                        $serverData['warps'] = $warps;
                        $this->core()->setServerData($serverData);
                        $sender->sendMessage('Точка варп ' . $args[1] . ' удалена');
                        return;
                    }
                }
            }
        }
        $serverData = $this->core()->getServerData();
        if ((!isset($serverData['warps']) || !count($serverData['warps']))) return $sender->sendMessage('§4[!] §cНа сервере нет установленных варпов');
        $items = [];
        foreach ($serverData['warps'] as $warp => $data) {
            $item = new Item($data['item']['id'], $data['item']['damage'], 1);
            $item->setCustomName($data['item']['name']);
            $items[] = $item;
        }
        $this->openInventory($sender, $items);
    }

    //protected array $tiles;
    public function openInventory(NBXPlayer $player, array $slots = [], bool $center = true) {
        $empty = []; for ($i = 0; $i < 27; $i++) $empty[] = (Item::get(102, 0, 1))->setCustomName('§0');
        
        $startIndex = floor((27 / 2) - (count($slots) / 2));
        $leftPart = array_slice($empty, 0, $startIndex);
        $rightPart = array_slice($empty, $startIndex);
        $resultArray = array_merge($leftPart, $slots, $rightPart);

        $tag = new CompoundTag('', [
            new StringTag('CustomName', 'Меню телепортации'),
            new IntTag('x', (int)$player->getFloorX()),
            new IntTag('y', (int)$player->getFloorY() + 3),
            new IntTag('z', (int)$player->getFloorZ()),
        ]);
        $tile = Tile::createTile('Chest', $player->getLevel(), $tag);
        $block = Block::get(Block::CHEST);
        $block->x = $tile->x;
        $block->y = $tile->y;
        $block->z = $tile->z;
        $block->level = $player->getLevel();
        $block->level->sendBlocks([$player], [$block]);
        if ($tile instanceof Chest) {
            for ($slot = 0; $slot < 27; $slot++) {
                $tile->getInventory()->setItem($slot, $resultArray[$slot]);
            }
            $player->getLevel()->addSound(new BlazeShootSound($player));
            $player->addWindow($tile->getInventory());
           //$this->tiles[$player->getName()] = $tile;
        }
        $player->setWarp(true);
        $player->addTile($tile);
    }
}