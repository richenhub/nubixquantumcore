<?php

namespace Richen\Engine\Additions;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\entity\LavaSlime;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\AddItemEntityPacket;
use pocketmine\Player;

class Help extends \Richen\Engine\Manager {
    private array $messages, $pages;
    private int $page;
    public array $eid;
    public array $players;

    public string $title, $info;

    public $pageNext;
    public $pagePrev;

	public function __construct() {
        $this->messages = [
            '§a/spawn §7§7- §eТелепортироваться на спавн',
            '§a/rtp §7- §eСлучайная телепортация',
            '§a/tpa (ник) §7- §eЗапрос на телепортацию к игроку',
            '§a/tpc §7- §eПринять запрос на телепортацию от игрока',
            '§a/sethome (название) §7- §eСоздать точку дома',
            '§a/home (название) §7- §eТелепортация на точку дома',
            '§a/delhome (название) §7- §eУдалить точку дома',
            '§a/warp (название) §7- §eТелепортация на варп',
            '§a/rg help §7- §eПомощь по привату',
            '§a/kit §7- §eПолучить набор вещей',
            '§a/rules §7- §eПравила сервера',
            '§a/chpwd §7- §eСменить пароль',
            '§a/job §7- §eУстроиться на работу',
            '§a/money §7- §eПосмотреть свой игровой баланс',
            '§a/topmoney §7- §eСписок богачей сервера',
            '§a/pay §7- §eПередать деньги другому игроку',
            '§a/list §7- §eСписок игроков онлайн',
            '§a/c help §7- §eПомощь по кланам',
            '§eСообщение с §6§l#§r§e, шепот §7- §fувидят игроки на расстоянии 5 блоков',
            '§eСообщения с §6§l!§r§e, §fувидят все игроки в глобальном чате',
            '§eСообщения с §6§l@§r§e, §fувидят игроки из вашего клана',
            '§a/fly §7- §eРежим полета (Донат возможность)',
            '§a/back §7- §eВернуться на точку смерти (Донат возможность)',
            '§a/top §7- §eТелепортация на поверхность (Донат возможность)',
            '§a/god §7- §eРежим бессмертия (Донат возможность)',
            '§a/heal §7- §eВылечить себя (Донат возможность)',
            '§a/feed §7- §eВосстановить голод (Донат возможность)',
            '§a/fix §7- §eПочина брони и инструментов (Донат возможность)',
            '§a/ext §7- §eПотушить себя от огня (Донат возможность)',
            '§a/ci §7- §eОчистить инвентарь (Донат возможность)',
            '§a/kill §7- §eУбить себя (Донат возможность)',
            '§a/day и /night §7- §eСменя дня и ночи (Донат возможность)',
            '§a/tp §7- §eТелепортация к игроку (Донат возможность)'
        ];

        $this->title = '§7● §dСтраница помощи §a%1$s§7/§6%2$s §7●';
        $this->info = '§7ударь стрелу, чтобы сменить страницу';
        $this->pageNext = '§7Следующая страница';
        $this->pagePrev = '§7Предыдущая страница';

        // foreach ($core->getServer()->getLevelByName('lobby')->getEntities() as $entity) {
        //     if ($entity instanceof LavaSlime) {
        //         $entity->close();
        //     }
        // }

        $this->pages = [[296,14,214],[294,9.7,214],[296,9.7,216]];
        $this->page = 1;
    }

    public function selectPage(Player $player) {
    	$username = mb_strtolower($player->getName());
        $this->eid[$username] = 0;
        $this->removePage($player);
        $itemsPerPage = 7;
        $totalItems = count($this->messages);
        $totalPages = ceil($totalItems / $itemsPerPage);
        if ($this->page > $totalPages) {
            $this->page = 1;
        } elseif ($this->page < 0) {
            $this->page = $totalPages;
        }
        $page = max($this->page, 1);
        $page = min($page, $totalPages);
        $startIndex = ($page - 1) * $itemsPerPage;
        $endIndex = min($startIndex + $itemsPerPage - 1, $totalItems - 1);
        $title = sprintf($this->title, $page, $totalPages);
        $pos = $this->pages[0];
        FloatingText::createCustomFloating($player, $pos[0], $pos[1], $pos[2], $title);
        FloatingText::createCustomFloating($player, $pos[0], $pos[1] - .5, $pos[2], $this->info);
        $y = .5;
        for ($i = $startIndex; $i <= $endIndex; $i++) {
            $y += .5;
            FloatingText::createCustomFloating($player, $pos[0], $pos[1] - $y, $pos[2], $this->messages[$i]);
        }
        $pos = $this->pages[1];
        FloatingText::createCustomFloating($player, $pos[0], $pos[1], $pos[2], $this->pagePrev);
        $pos = $this->pages[2];
        FloatingText::createCustomFloating($player, $pos[0], $pos[1], $pos[2], $this->pageNext);
    }

    public function spawnArrows(Player $player) {
        $this->removeEntities($player);
		$item1 = Item::get(262, 5, 1);
		$item2 = Item::get(262, 10, 1);
        
		$pkk = new RemoveEntityPacket;
		$pkk->eid = $this->eid[mb_strtolower($player->getName())];
		$player->dataPacket($pkk);

        $this->updateItem($player, $item1, $this->pages[1]);
        $this->updateItem($player, $item2, $this->pages[2]);
        $this->updateForPlayer($player);
    }

    public function updateItem(Player $player, Item $item, array $pos) {
    	$username = mb_strtolower($player->getName());
        
        $id = Entity::$entityCount++;

		$pk = new AddItemEntityPacket;
		$pk->eid = $id;
		$pk->item = $item;
		$pk->x = $pos[0] + 0.5;
		$pk->y = $pos[1] + 1;
		$pk->z = $pos[2] + 0.5;
		$pk->speedX = 0;
		$pk->speedY = 0;
		$pk->speedZ = 0;
		$player->dataPacket($pk);

		$this->eid[$username] = $id;
    }

    public function removePage(Player $player) {
        $pos = $this->pages[0];
        $y = 0;
        for ($i = 0; $i <= 10; $i++) {
            FloatingText::removePreCustomFloating($player, $pos[0], $pos[1] - $y, $pos[2]);
            $y += .5;
        }
        $pos = $this->pages[1];
        FloatingText::removePreCustomFloating($player, $pos[0], $pos[1], $pos[2]);
        $pos = $this->pages[2];
        FloatingText::removePreCustomFloating($player, $pos[0], $pos[1], $pos[2]);
    }

    public function nextPage(Player $player) {
        $this->page++;
        $this->selectPage($player);
        $player->getLevel()->addSound((new \pocketmine\level\sound\ButtonClickSound($player)), [$player]);
    }

    public function prevPage(Player $player) {
        $this->page--;
        $this->selectPage($player);
        $player->getLevel()->addSound((new \pocketmine\level\sound\ButtonClickSound($player)), [$player]);
    }

    public function updateForPlayer($player) {
        NPC::createInvisibleNPC($player, 'prevpage', $this->pages[1], 0.9);
        NPC::createInvisibleNPC($player, 'nextpage', $this->pages[2], 0.9);
    }

    public function removeEntities(Player $player) {
		foreach ($player->getServer()->getLevels() as $level) {
            foreach ($level->getEntities() as $entity) {
		        if ($entity instanceof LavaSlime && !$entity instanceof Player) {
                    switch ($entity->getNameTag()) {
                        case 'nextpage':
                        case 'prevpage':
                            $entity->despawnFrom($player);
                            break;
                    }
			    }
            }
		}
	}

    public function onSelectMenuItem(Entity $target, Player $player) {
        if ($target instanceof LavaSlime) {
            switch ($target->getNameTag()) {
                case 'nextpage': $this->nextPage($player); break;
                case 'prevpage': $this->prevPage($player); break;
            }
        }
    }
}