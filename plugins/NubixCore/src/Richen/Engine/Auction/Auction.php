<?php 

declare(strict_types=1);

namespace Richen\Engine\Auction;

use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\scheduler\CallbackTask;
use pocketmine\tile\Tile;

use function array_chunk;
use function array_filter;
use function array_map;
use function count;
use function in_array;
use function max;
use function number_format;
use function rand;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;

class Auction extends \Richen\Engine\Manager {
	const DEDUCTIBLE = 0x61103ed4;
	const TYPE_CURRENT_PAGE = 2;
	const AUTODONATE_SITE = '§6§lсайт§r';
	const INVENTORY_TITLE = '§lАукцион вещей';
    const AUCTION_SIZE = 300;
    const MIN_PRICE = 1;
    const MAX_PRICE = 1000000;
    const RESTRICTED_ITEM_IDS = [
		BlockIds::COMMAND_BLOCK,
        BlockIds::REPEATING_COMMAND_BLOCK,
        BlockIds::CHAIN_COMMAND_BLOCK
	];
	public $data, $invalidated_items;
	public int $ticksFromSave = 0;
	public bool $hasChanged = false;
	public array $viewers = [], $availableSlots = [], $cachedPages = [];
	public static $instance;
    public static function getInstance(): Auction { return self::$instance; }
    public function __construct() {
        self::$instance = $this;
        $this->initAvailableSlots();
        
		$this->data = $this->core()->conf('auction_data', 1)->config();
		$this->invalidated_items =  $this->core()->conf('invalidated_items', 1)->config();
		Tile::registerTile(\Richen\Engine\Additions\VirtualChest::class);
		$this->serv()->getScheduler()->scheduleRepeatingTask(new CallbackTask(array($this, 'onUpdate')), 20 * 10);
    }

    public function onUpdate() {
		if (++$this->ticksFromSave === 30) {
			$this->doSave();
			$this->ticksFromSave = 0;
		}
		$this->refreshPages();
	}

    public function refreshPages(): void {
		$data = $this->data->getAll();
		$countBefore = count($data);

		$this->invalidateExpiredItems();

		$data = $this->data->getAll();
		$countAfter = count($data);

		if ($countBefore !== $countAfter) {
			$this->hasChanged = true;
		}

		$monetaryUnit = '$';
		$currentTime = Helper::breakTime();

		$data = array_map(function(array $data) use ($monetaryUnit, $currentTime): Item {
			$item = Helper::deserializeItem($data['item']);

			if (!$item->hasCustomName()) {
				$itemName = $item->hasEnchantments() ? "§r§b§l" : "§r§f§l";
				$itemName .= \Richen\Engine\ItemNamesConverter::convertName($item);
			} else {
				$itemName = "§r" . $item->getCustomName();
			}

			$itemName .= "§r §7x" . $item->getCount();

			$item->setCustomName(
				$itemName . "§r\n\n" .
				"§fЦена: §a" . Helper::toPrettyNumber(number_format($data['price'])) . " §6" . $monetaryUnit . "§r\n\n" .
				"§fПродавец: §e" . $data['playerWithCase'] . "§r"
			);

			$nbt = $item->getNamedTag();
			$nbt->owner = new StringTag('owner', $data['player']);
			$nbt->price = new IntTag('price', $data['price']);
			$nbt->expirationDate = new IntTag('expirationDate', $data['exp']);
			$nbt->uniqueKey = new IntTag('uniqueKey', $data['key']);

			return $item->setNamedTag($nbt);
		}, $data);

		$this->cachedPages = array_chunk($data, count($this->availableSlots), true);
		if($this->hasChanged and count($this->viewers) > 0){
			$this->showChangesToViewers();
		}
		$this->hasChanged = false;
	}

    public function hasItemInvalidated(int $uniqueKey): bool {
		return !$this->data->exists($uniqueKey);
	}

    public function showChangesToViewers(): void {
		$players = [];
		foreach ($this->serv()->getOnlinePlayers() as $p) {
			if ($p->isOnline() and isset($this->viewers[$p->getName()])) {
				$players[] = [
					$p,
					$this->viewers[$p->getName()][0][0],
					$this->viewers[$p->getName()][self::TYPE_CURRENT_PAGE]
				];
			}
		}
		foreach ($players as [$playerInstance, $chest, $page]) {
			if (!$chest instanceof \Richen\Engine\Additions\VirtualChest or $chest->closed) {
				continue;
			}
			if (!$chest->getInventory() instanceof \Richen\Engine\Additions\PersonalDoubleInventory) {
				continue;
			}
			$page = isset($this->cachedPages[$page]) ? 0 : -1;
			$this->openPage($playerInstance, $chest->getInventory(), $page);
		}
	}

    public function isViewingAuction(\pocketmine\Player $player): bool {
		if (isset($this->viewers[$player->getName()])) {
			if ($player->distance($this->viewers[$player->getName()][3]) > 5.0) {
				unset($this->viewers[$player->getName()]);
				return false;
			}
			return true;
		}
		return false;
	}

	public function upForAuction(\pocketmine\Player $player, Item $item, int $price): void {
		$player->getInventory()->clear($player->getInventory()->getHeldItemSlot());

		$data = $this->data->getAll();

		$generateUniqueKey = function() use ($data): int {
			while (!isset($key) or isset($data[$key])) {
				$key = rand(0, 0xfff);
			}
			return $key;
		};

        $uniqueKey = $generateUniqueKey();

		$data[$uniqueKey] = [
			'player' => $player->getLowerCaseName(),
			'playerWithCase' => $player->getName(),
			'item' => Helper::serializeItem($item),
			'price' => $price,
			'exp' => Helper::breakTime() + 60 * 60 * 24,
			'key' => $uniqueKey
		];

		$this->data->setAll($data);

		$this->hasChanged = true;
		$this->refreshPages();

		$rusItemName = \Richen\Engine\ItemNamesConverter::convertName($item);
		$prettyPrice = Helper::toPrettyNumber(number_format($price));
		$count = $item->getCount();
		$monetaryUnit = '$';
		$player->sendMessage(
			"§a➛ §fНа аукцион выставлен предмет §l" . $rusItemName . " §r§7x" . $count . " §fпо цене §6" . $prettyPrice . " §6" . $monetaryUnit
		);
		$player->sendMessage("§a➛ §7Если предмет не купят в течение §b24 ч§7, ты сможешь его вернуть по команде §a/auc back!");
	}

	public function pullFromTheAuction(\pocketmine\Player $player, int $uniqueKey, bool $causePurchased = false): void {
		$data = $this->data->getAll();
		if (!isset($data[$uniqueKey])) {
			if($causePurchased){
				$player->sendMessage("§a➛ §cПредмет невозможно приобрести!");
			}else{
				$player->sendMessage("§a➛ §cПредмет был снят с продажи!");
			}
			return;
		}

		$remains = count($data) % count($this->availableSlots);
		$remains -= 1;

		$item = Helper::deserializeItem($data[$uniqueKey]['item']);
		if (!$player->getInventory()->canAddItem($item)) {
			$player->sendMessage("§a➛ §7В твоём инвентаре §cнет места!");
			return;
		}
		$what = \Richen\Engine\ItemNamesConverter::convertName($item) . " §7x" . $item->getCount() . "§r";

		if ($causePurchased) {
            $cash = $this->core()->cash();
            $ucash1 = $cash->getUserCash($player->getName());
            $ucash2 = $cash->getUserCash($ownerName = $data[$uniqueKey]['player']);
			$ucash1->delMoney($price = $data[$uniqueKey]['price']);
			$ucash2->addMoney($price);

			$prettyPrice = Helper::toPrettyNumber(number_format($price));
			$monetaryUnit = '$';
			$player->sendMessage("§a➛ §fТы купил(а) на аукционе §a§l" . $what . " §r§fза §6" . $prettyPrice . " §6" . $monetaryUnit);
			$player->sendTitle("§aПОКУПКА", "§a- §3" . $prettyPrice . " §a" . $monetaryUnit, 20, 70, 20);

			$owner = $this->serv()->getPlayerExact($ownerName);
			if ($owner instanceof \pocketmine\Player and $owner->isOnline()) {
				$owner->sendMessage("\n§a➛ §fВаш предмет §e$what §fкупили на аукционе за §6" . $prettyPrice . " §b" . $monetaryUnit);
				$owner->sendTitle("§dАУКЦИОН", "§a+ §3" . $prettyPrice . " §6" . $monetaryUnit, 20, 70, 20);
			}
		}else{
			$player->sendMessage("§a➛ §fС продажи снят предмет §b". $what);
		}

		$player->getInventory()->addItem($item);

		unset($data[$uniqueKey]);
		$this->data->setAll($data);
		$this->hasChanged = true;

		if ($remains === 0) {
			$this->refreshPages();
			$this->showChangesToViewers();
		}
	}

	public function openPage(\pocketmine\Player $player, \Richen\Engine\Additions\PersonalDoubleInventory $inventory, int $pageDirection): void {
		if (!$this->isViewingAuction($player)) return;
		for ($i = 0; $i < 54; ++$i) $inventory->setItem($i, new Item(BlockIds::AIR));
		if (empty($this->cachedPages)) {
			$item = (new Item(BlockIds::STAINED_CLAY, 14))->setCustomName(
				"§r§c§lПУСТО§r\n\n" .
				"§fСейчас никто ничего не продаёт!\n\n" .
				"§fПродать предметы: §6/auc sell"
			);
			$this->fillWindowSlot($inventory, 22, $item);
			return;
		}

		$page = $this->viewers[$player->getName()][self::TYPE_CURRENT_PAGE] + $pageDirection;
		if (!isset($this->cachedPages[$page])) {
			if ($pageDirection < 0) {
				$page = max(0, count($this->cachedPages) - 1);
			} else {
				$page = 0;
			}
		}
		$this->viewers[$player->getName()][self::TYPE_CURRENT_PAGE] = $page;

		$data = $this->data->getAll();
        $cash = $this->core()->cash();
        $ucash = $cash->getUserCash($player->getName());
		$prettyMoney = Helper::toPrettyNumber(number_format($money = $ucash->getMoney()));
		$monetaryUnit = '$';

		$num = 0;
		foreach ($this->cachedPages[$page] as $uniqueKey => $item) {
			if (!isset($data[$uniqueKey])) {
				continue;
			}

			$item = clone $item;
			$nbt = $item->getNamedTag();
			if ($nbt->owner->getValue() !== $player->getLowerCaseName()) {
				$customName = $item->getCustomName();
				$customName .= "\n\n§r§fТвои деньги: §7" . $prettyMoney . " §6" . $monetaryUnit;
				if ($money < $data[$uniqueKey]['price']){
					$customName .= "\n\n§cДля покупки недостаточно денег";
				} else {
					$customName .= "\n§fНажми два раза для §a§lпокупки§r";
				}
			} else {
				$customName = $item->getCustomName() . "\n\n§7(Твой предмет)\n§fНажми дважды для §c§lснятия§r §fс продажи";
			}
			if ($item->hasEnchantments()) {
				$customName .= "\n ";
			}
			$this->fillWindowSlot($inventory, $this->availableSlots[$num++], $item->setCustomName($customName));
		}

		if (count($this->cachedPages) > 1) {
			$previousPage = (new Item(ItemIds::PAPER, 0, 1))->setCustomName(
				"§r§a<< Предыдущая страница§r"
			);
			$nbt = $previousPage->getNamedTag();
			$nbt->page = new ByteTag('page', -1);
			$this->fillWindowSlot($inventory, 45, $previousPage->setNamedTag($nbt));

			$nextPage = (new Item(ItemIds::PAPER, 0, 1))->setCustomName(
				"§r§aСледующая страница >>§r"
			);
			$nbt = $nextPage->getNamedTag();
			$nbt->page = new ByteTag('page', 1);
			$this->fillWindowSlot($inventory, 53, $nextPage->setNamedTag($nbt));
		}

		$currentPage = Helper::toPrettyNumber($page + 1);
		$maxPage = Helper::toPrettyNumber(count($this->cachedPages));
		$info = (new Item(ItemIds::ARROW, 0, 1))->setCustomName(
			"§r§fТы на странице §b§l" . $currentPage . "§r §fиз §b§l" . $maxPage . "§r\n\n" .
			"§fПродавать свои вещи и зарабатывать: §a§l/auc sell§r"
		);
		$this->fillWindowSlot($inventory, 49, $info);
	}

	public function open(\pocketmine\Player $player) {
		if (!$player->isValid()){
			return;
		}
		if ($this->isViewingAuction($player)) {
			return;
		}

		

		$blockReplaced = ($level = $player->getLevel())->getBlock($vector3 = $player->floor()->subtract(0, 3));
		$blockReplaced2 = $level->getBlock($pairVector3 = $vector3->getSide(\pocketmine\math\Vector3::SIDE_WEST));

		$this->updateBlockImmediately($player, \pocketmine\block\Block::get(BlockIds::CHEST, 2, \pocketmine\level\Position::fromObject($vector3)));
		$this->updateBlockImmediately($player, \pocketmine\block\Block::get(BlockIds::CHEST, 2, \pocketmine\level\Position::fromObject($pairVector3)));

		$chest = Tile::createTile(
			'VirtualChest',
			$level,
			Helper::createTileNBT('Chest', self::INVENTORY_TITLE, $vector3, $pairVector3),
			$playerName = $player->getName()
		);
		$chest2 = Tile::createTile(
			'VirtualChest',
			$level,
			Helper::createTileNBT('Chest', self::INVENTORY_TITLE, $pairVector3, $vector3),
			$playerName
		);

        if ($chest instanceof \Richen\Engine\Additions\VirtualChest && $chest2 instanceof \Richen\Engine\Additions\VirtualChest) {
			$inventory = new \Richen\Engine\Additions\PersonalDoubleInventory($chest, $chest2, $playerName);
		    $chest->setDoubleInventory($inventory);
		    $chest2->setDoubleInventory($inventory);
		    $chest->setShouldBeSpawned();
    		$chest2->setShouldBeSpawned();
		    $chest->spawnTo($player);
		    $chest2->spawnTo($player);
        } else {
            return;
        }
		$this->viewers[$playerName] = [[$chest, $chest2], [$blockReplaced, $blockReplaced2], self::TYPE_CURRENT_PAGE => 0, $player->floor()];
		$this->openPage($player, $inventory, 0);
		$this->serv()->getScheduler()->scheduleDelayedTask(new CallbackTask (array($this, 'owin'), array($inventory, $player)), 5); 
	}

    public function owin(\Richen\Engine\Additions\PersonalDoubleInventory $inventory, \pocketmine\Player $player) {
        $this->openWindow($inventory, $player, $player->getName());
    }

	public function slotsLimited(\pocketmine\Player $player): bool {
		if (!$this->hasPermission($player, 'auc.no_limit')) return false;
		$currentTime = Helper::breakTime();
		return count(array_filter($this->data->getAll(), function(array $data) use ($player, $currentTime): bool {
            return $data['player'] === $player->getLowerCaseName() && $data['exp'] > $currentTime;
        })) >= ($this->core()->conf('groups')->config()->getAll()[$player instanceof \Richen\Custom\NBXPlayer ? $player->getGroupName() : 'guest']['aucSlots']);
	}

	public function noFreeSpace(): bool {
		$currentTime = Helper::breakTime();
		return count(array_filter($this->data->getAll(), function(array $data) use ($currentTime): bool {
            return $data['exp'] > $currentTime;
        })) >= self::AUCTION_SIZE;
	}

	public function openWindow(\Richen\Engine\Additions\PersonalDoubleInventory $inventory, ?\pocketmine\Player $player, string $playerName): void {
		if ($player instanceof \pocketmine\Player && $player->isOnline()) {
			$holder = $inventory->getHolder();
			if($holder !== null && !$holder->closed && $player->distance($holder) < 5.0){
				$player->addWindow($inventory);
				return;
			}
		}
		unset($this->viewers[$playerName]);
	}

	public function fillWindowSlot(\Richen\Engine\Additions\PersonalDoubleInventory $inventory, int $slot, Item $item): void {
		$nbt = $item->getNamedTag() ?? new CompoundTag();
		$nbt->auctionWindowItem = new ByteTag('auctionWindowItem', 1);
		(function() : void{
			$this->{'block'} = null;
		})->call($item);
		$inventory->setItem($slot, $item->setNamedTag($nbt));
	}

	private function updateBlockImmediately(\pocketmine\Player $recipient, \pocketmine\block\Block $block) {
		$pk = new \pocketmine\network\mcpe\protocol\UpdateBlockPacket();
		$pk->blockId = BlockIds::CHEST;
		$pk->blockData = 0;
		$pk->x = $block->x;
		$pk->z = $block->z;
		$pk->y = $block->y;
		$pk->flags = \pocketmine\network\mcpe\protocol\UpdateBlockPacket::FLAG_ALL;
		$recipient->dataPacket($pk);
	}

	public function addToDelayedClose(\pocketmine\Player $player) {
		if (!$this->isViewingAuction($player)) {
			return;
		}
		$this->serv()->getScheduler()->scheduleDelayedTask(new CallbackTask (array($this, 'del'), array($player)), 2); 
	}

	public function del($player) {
	    $this->removeBlockAndTile($player);
	}

	public function removeBlockAndTile(\pocketmine\Player $player = null) {
		if (!$player instanceof \pocketmine\Player or !$player->isOnline()) return;
		if (!isset($this->viewers[$playerName = $player->getName()])) return;
		$tiles = $this->viewers[$playerName][0];
		$blocksReplaced = $this->viewers[$playerName][1];
		if ($player->isValid()) {
			$player->getLevel()->sendBlocks([$player], $blocksReplaced, \pocketmine\network\mcpe\protocol\UpdateBlockPacket::FLAG_ALL_PRIORITY);
		}
		foreach ($tiles as $tile) {
			if ($tile instanceof \Richen\Engine\Additions\VirtualChest and !$tile->closed) $tile->close();
		}
		unset($this->viewers[$playerName]);
	}

	public function hasInvalidatedItems(\pocketmine\Player $player): bool {
		return $this->invalidated_items->exists($player->getLowerCaseName());
	}

	public function invalidateExpiredItems(): void {
		$currentTime = Helper::breakTime();
		foreach ($this->data->getAll() as $key => $data) {
			if ($data['exp'] < $currentTime){
				$this->data->remove($key);
				$d = (array)$this->invalidated_items->get($playerName = $data['player'], []);
				$d[] = $data['item'];
				$this->invalidated_items->set($playerName, $d);
			}
		}
	}

	public function doSave(): void {
		//if($this->hasChanged){
			$this->data->save();
		//}
		//if($this->hasChanged){
			$this->invalidated_items->save();
		//}
	}

	public function onDisable(){
		$this->doSave();
	}

	public function initAvailableSlots(): void {
		$unavailableSlots = [0, 8, 9, 17, 18, 26, 27, 35, 36, 44, 45, 53];
		for ($i = 10; $i < 44; ++$i) {
			if (in_array($i, $unavailableSlots, true)) {
				continue;
			}
			$this->availableSlots[] = $i;
		}
	}
}