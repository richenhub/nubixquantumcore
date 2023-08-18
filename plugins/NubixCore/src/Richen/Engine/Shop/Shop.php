<?php

namespace Richen\Engine\Shop;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\entity\LavaSlime;
use pocketmine\math\Vector3;
use pocketmine\Player;
use Richen\Engine\Additions\FloatingText;
use Richen\Engine\Additions\NPC;

class Shop extends \Richen\Engine\Manager {
    private array $items, $messages;
    private int $number, $maxnumber;
    private int $count, $maxcount;
    private int $price;
    private array $eid;

    private array $shop;

    public array $players;

	public function __construct() {
        $this->messages = [
            'success' => '§eВаша покупка добавлена в ваш инвентарь!',
            'error' => '§cУ вас недостаточно средств на счету для покупки!',
            'shopname' => '§e● §fМагазин §e●§r',
            'shopinfo' => '§7Для открытия магазина, нажмите на сундук!§r',
            '+item' => '§eСледующий товар§r',
            '-item' => '§eПредыдущий товар§r',
            '+count' => '§aКоличество +§r',
            '-count' => '§cКоличество -§r',
            'shopchest' => '§7Нажмите, чтобы купить!§r',
            'itemname' => '§e● §f§l%s §r§7(%s шт) §8- §a%s$ §e●§r'
        ];

        // foreach ($core->getServer()->getLevelByName('lobby')->getEntities() as $entity) {
        //     if ($entity instanceof LavaSlime) {
        //         $entity->close();
        //     }
        // }

        $off = [[[-1.5,0],[1.5,0]], [[1.5,0],[-1.5,0]], [[0,1.5],[0,-1.5]], [[0,-1.5],[0,1.5]],];
        $chest = [278, 10, 238];
        $i = 1;
        $this->shop = [
            'shopchest' => $chest,
            '+item' => [$chest[0] + $off[$i][0][0], $chest[1]+.7, $chest[2] + $off[$i][0][1]],
            '-item' => [$chest[0] + $off[$i][0][0], $chest[1]+.1, $chest[2] + $off[$i][0][1]],
            '+count' => [$chest[0] + $off[$i][1][0], $chest[1]+.7, $chest[2] + $off[$i][1][1]],
            '-count' => [$chest[0] + $off[$i][1][0], $chest[1]+.1, $chest[2] + $off[$i][1][1]],

            'shopname' => [$chest[0], $chest[1] + 1, $chest[2]],
            'shopinfo' => [$chest[0], $chest[1] + 0.4, $chest[2]],
            'itemname' => [$chest[0], $chest[1] + 1, $chest[2]],
        ];

        $this->items = [
            0 => [17,0,10,'Дуб'],
            1 => [17,1,15,'Cосна'],
            2 => [17,2,15,'Береза'],
            3 => [17,3,15,'Пальма'],
            4 => [3,1,100,'Земля'],
            5 => [4,1,25,'Булыжник'],
            6 => [345,1,1000,'Компас'],
            7 => [347,1,1000,'Часы'],
            8 => [346,1,2000,'Удочка'],
            9 => [261,1,200,'Лук'],
            10 => [262,1,3,'Стрелы'],
            11 => [354,1,200,'Тортик'],
            12 => [297,1,20,'Хлебушек'],
            13 => [76,1,50,'Кровавый факел'],
            14 => [98,1,20,'Каменный кирпич'],
            15 => [20,1,20,'Стеклышко'],
            16 => [288,1,10,'Перо'],
            17 => [35,0,20,'Белая шерсть'],
            18 => [35,1,20,'Оранжевая шерсть'],
            19 => [35,2,20,'Фиолетовая шерсть'],
            20 => [35,3,20,'Аква шерсть'],
            21 => [35,4,20,'Желтая шерсть'],
            22 => [35,5,20,'Светло-зеленая шерсть'],
            23 => [35,6,20,'Розовая шерсть'],
            24 => [35,7,20,'Темно-серая шерсть'],
            25 => [35,8,20,'Серая шерсть'],
            26 => [35,9,20,'Темно-аквамариновая шерсть'],
            27 => [35,10,20,'Темно-фиолетовая шерсть'],
            28 => [35,11,20,'Синия шерсть'],
            29 => [35,12,20,'Коричневая шерсть'],
            30 => [35,13,20,'Темно-зеленая шерсть'],
            31 => [35,14,20,'Красная шерсть'],
            32 => [35,15,20,'Черная шерсть'],
            33 => [450,0,3499,'Тотем'],
            34 => [380,0,399,'Котел'],
            35 => [379,0,799,'Зельеварка'],
            36 => [374,0,24,'Колбочка'],
            37 => [375,0,12,'Бурдюк'],
            38 => [376,0,12,'Глаз'],
            39 => [377,0,14,'Пыль'],
            40 => [378,0,14,'Магма'],
            41 => [381,0,55,'Эко-эндер мира'],
            42 => [382,0,24,'Алмазный арбуз'],
            43 => [369,0,85,'Палка блейза'],
            44 => [370,0,44,'Слеза гаста'],
            45 => [372,0,8,'Бородавка'],
            46 => [361,0,35,'Семена тыквы'],
            47 => [362,0,44,'Семена арбуза'],
            48 => [296,0,75,'Семена пшеницы'],
            49 => [338,0,200,'Тросник'],
            50 => [287,0,40,'Паутинка'],
            51 => [391,0,80,'Морковка'],
            52 => [392,0,80,'Паутинка'],
            53 => [457,0,4,'Редька'],
        ];
        
        $this->number = 0;
        $this->maxnumber = count($this->items) - 1;
        $this->count = 1;
        $this->maxcount = 64;
        $this->price = $this->items[0][2];
    }

    public function removeShop(Player $player) {
        $shop = $this->shop;

        foreach ($shop as $key => $pos) {
            FloatingText::removePreCustomFloating($player, $pos[0], $pos[1], $pos[2]);
        }

        foreach($player->getServer()->getOnlinePlayers() as $players) {
            $player->showPlayer($players);
        }

        $si = $shop['shopname'];
        FloatingText::createCustomFloating($player, $si[0], $si[1], $si[2], $this->messages['shopname']);
        $si = $shop['shopinfo'];
        FloatingText::createCustomFloating($player, $si[0], $si[1], $si[2], $this->messages['shopinfo']);

        $pk = new \pocketmine\network\mcpe\protocol\RemoveEntityPacket;
        $pk->eid = $this->eid[mb_strtolower($player->getName())];
        $player->dataPacket($pk);

        $this->removeEntities($player);
        $si = $this->shop['shopchest'];
        $this->onAnimationChest($player, $si[0], $si[1], $si[2], false);
    }

    public function spawnShopForPlayer(Player $player) {
        $si = $this->shop['shopname'];
        FloatingText::createCustomFloating($player, $si[0], $si[1], $si[2], $this->messages['shopname']);
        $si = $this->shop['shopinfo'];
        FloatingText::createCustomFloating($player, $si[0], $si[1], $si[2], $this->messages['shopinfo']);
    }

    public function checkForCloseShop(Player $player) {
        $username = mb_strtolower($player->getName());
        $pos = $this->shop['shopchest'];
        if ($player->distance(new Vector3($pos[0], $pos[1], $pos[2])) > 5) {
            if (isset($this->players[$username])) {
                $this->removeShop($player);
                unset($this->players[$username]);
            }
        }
    }

    public function unregisterPlayer(Player $player) {
        $username = mb_strtolower($player->getName());
    	if (isset($this->shop[$username])) unset($this->players[$username]);
    	if (isset($this->eid[$username])) unset($this->eid[$username]);
    }

    public function updateForPlayer($player) {
		foreach (['+item', '-item', '+count', '-count'] as $btn) {
            NPC::createInvisibleNPC($player, $btn, $this->shop[$btn], 0.2);
        }
    }

    public function onSelectMenuItem(Entity $target, Player $player) {
        if ($target instanceof LavaSlime) {
            switch ($target->getNameTag()) {
                case '+item':
                    $this->onTapTrue('Кнопка следующее', $player);
                    break;
                case '-item':
                    $this->onTapTrue('Кнопка предыдущее', $player);
                    break;
                case '+count':
                    $this->onTapTrue('Кнопка+', $player);
                    break;
                case '-count':
                    $this->onTapTrue('Кнопка-', $player);
                    break;
            }
        }
    }

    public function removeEntities(Player $player) {
		foreach ($player->getServer()->getLevels() as $level) {
            foreach ($level->getEntities() as $entity) {
		        if ($entity instanceof LavaSlime && !$entity instanceof Player) {
                    switch ($entity->getNameTag()) {
                        case '+item':
                        case '-item':
                        case '+count':
                        case '-count':
                            $entity->despawnFrom($player);
                            break;
                    }
			    }
            }
		}
	}

    public function onChestClick($event) {
    	$player = $event->getPlayer();
        $username = mb_strtolower($player->getName());
        $block = $event->getBlock();

    	$x = $block->getX();
        $y = $block->getY();
        $z = $block->getZ();

        $si = $this->shop['shopchest'];
    	switch ([$x, $y, $z]) {
            case $si:
                $event->setCancelled();
                if (isset($this->players[$username])) {
                    $this->onTapTrue('Кнопка купить', $player);
                } else {
                    if (!isset($this->players[$username])) {
                        $this->onAnimationChest($player, $x, $y, $z, true);
                        
                        $this->eid[$username] = 0;
                    
                        $this->updateItem($player);
                        
                        $this->players[$username] = $player;
                        
                        $this->updateForPlayer($player);

                        $pos = $this->shop['shopinfo'];
                        FloatingText::removePreCustomFloating($player, $pos[0], $pos[1], $pos[2]);
                        $pos = $this->shop['shopname'];
                        FloatingText::removePreCustomFloating($player, $pos[0], $pos[1], $pos[2]);

                        foreach ($player->getServer()->getOnlinePlayers() as $players) {
                            $player->hidePlayer($players);
                        }

                        foreach (['+item', '-item', '+count', '-count', 'shopchest'] as $btn) {
                            $data = $this->shop[$btn];
                            FloatingText::createCustomFloating($player, $data[0], $data[1], $data[2], $this->messages[$btn]);
                        }

                        foreach ($player->getLevel()->getEntities() as $entity) {
                            if ($entity instanceof LavaSlime) {
                                if (in_array($entity->getNameTag(), ['+item', '-item', '+count', '-count'])) {
                                    $entity->spawnTo($player);
                                    $entity->addEffect(Effect::getEffect(14)->setDuration(2147483647)->setAmplifier(0)->setVisible(false));
                                    $entity->setDataProperty(Entity::DATA_SCALE, Entity::DATA_TYPE_FLOAT, 0.2);
                                }
                            }
                        }
                    }	
                }
                break;
        }
    }

    public function onAnimationChest(Player $player, int $x, int $y, int $z, bool $case = false) {
	    $pk = new \pocketmine\network\mcpe\protocol\BlockEventPacket();
		$pk->x = $x;
		$pk->y = $y;
		$pk->z = $z;
		$pk->case1 = 1;
		$pk->case2 = $case ? 2 : 0;
		$player->dataPacket($pk);
	}

    public function updateItem(Player $player, int $count = 1) {
    	$username = mb_strtolower($player->getName());
    	$id = $this->items[$this->number][0];
        $damage = $this->items[$this->number][1];
    	$name = $this->items[$this->number][3];

        $pos = $this->shop['itemname'];
        FloatingText::createCustomFloating($player, $pos[0], $pos[1], $pos[2], sprintf($this->messages['itemname'], $name, $this->count, $this->price));

		$pkk = new \pocketmine\network\mcpe\protocol\RemoveEntityPacket;
		$pkk->eid = $this->eid[$username];
		$player->dataPacket($pkk);

		$item = Item::get($id, $damage, $count);
        
        $id = Entity::$entityCount++;

        $chest = $this->shop['shopchest'];
		$pk = new \pocketmine\network\mcpe\protocol\AddItemEntityPacket;
		$pk->eid = $id;
		$pk->item = $item;
		$pk->x = $chest[0] + 0.5;
		$pk->y = $chest[1] + 1.1;
		$pk->z = $chest[2] + 0.5;
		$pk->speedX = 0;
		$pk->speedY = 0;
		$pk->speedZ = 0;
		$player->dataPacket($pk);

		$this->eid[$username] = $id;
    }

    public function onButtonCount(Player $player, bool $check) {
        $this->count = ($check) ? (($this->maxcount === $this->count) ? 1 : ++$this->count) : (($this->count === 1) ? 64 : --$this->count);
        $this->price = $this->items[$this->number][2] * $this->count;
        $this->updateItem($player, $this->count);
    	$player->getLevel()->addSound((new \pocketmine\level\sound\ButtonClickSound($player)), [$player]);
    }

    public function onButton(Player $player, bool $check) {
        $this->number = ($check) ? (($this->number == $this->maxnumber) ? 0 : ++$this->number) : (($this->number == 0) ? $this->maxnumber : --$this->number);
        $this->count = 1;
        $this->price = $this->items[$this->number][2] * $this->count;
        $this->updateItem($player);
    	$player->getLevel()->addSound((new \pocketmine\level\sound\ButtonClickSound($player)), [$player]);
    }

    public function onBuy(Player $player) {
		$price = $this->price;
        $cash = $this->core()->cash();
        $ucash = $cash->getUserCash($player->getName());
		if ($price <= $ucash->getMoney()) {
            $ucash->delMoney($price);
			$player->getInventory()->addItem(Item::get($this->items[$this->number][0], $this->items[$this->number][1], $this->count));
			$player->addActionBarMessage($this->messages['success']);
			$player->getLevel()->addSound((new \pocketmine\level\sound\ExpPickupSound($player)), [$player]);
		} else {
			$player->addActionBarMessage($this->messages['error']);
			$player->getLevel()->addSound((new \pocketmine\level\sound\DoorBumpSound($player)), [$player]);
		}
    }

    public function onTapTrue(string $key, Player $player) {
    	switch ($key) {
    		case 'Кнопка+': $this->onButtonCount($player, true); break;
            case 'Кнопка-': $this->onButtonCount($player, false); break;
    		case 'Кнопка следующее': $this->onButton($player, true); break;
    		case 'Кнопка предыдущее': $this->onButton($player, false); break;
    		case 'Кнопка купить': $this->onBuy($player); break;
    	}
    }
}
?>