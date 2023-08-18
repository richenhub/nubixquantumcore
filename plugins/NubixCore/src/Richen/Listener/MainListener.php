<?php 

namespace Richen\Listener;

use Richen\Custom\NBXPlayer;
use pocketmine\entity\Effect;
use Richen\Engine\Utils;

class MainListener extends \Richen\Engine\Manager implements \pocketmine\event\Listener {
	private $confirmations = [];
    public function onPlayerCreation(\pocketmine\event\player\PlayerCreationEvent $ev) {
		$ev->setPlayerClass(NBXPlayer::class);
	}
    
	public function handleInventoryTransaction(\pocketmine\event\inventory\InventoryTransactionEvent $ev) {
        $transaction = $ev->getQueue();
        if ($transaction instanceof \pocketmine\inventory\SimpleTransactionQueue && !\Richen\Engine\Auction\Auction::getInstance()->isViewingAuction($player = $transaction->getPlayer())) {
            return;
        }
        $ev->setCancelled(true);
        foreach($transaction->getTransactions() as $_transaction){
            $inventory = $_transaction->getInventory();
            if ($inventory instanceof \Richen\Engine\Additions\PersonalDoubleInventory) {
                if ($inventory->getViewerName() !== $player->getName()) return;
                $item = $inventory->getItem($_transaction->getSlot());
                if(!$item->hasCompoundTag()) return;
                $nbt = $item->getNamedTag();
                if (!isset($nbt->auctionWindowItem)) return;
                if (isset($nbt->page)) {
                    \Richen\Engine\Auction\Auction::getInstance()->openPage($player, $inventory, $nbt->page->getValue());
                    return;
                }
                if(!isset($nbt->owner) or !isset($nbt->uniqueKey)){
                    return;
                }
                $uniqueKey = $nbt->uniqueKey->getValue();
                if(\Richen\Engine\Auction\Auction::getInstance()->hasItemInvalidated($uniqueKey)){
                    $player->sendMessage("§a➛ §cЭтот предмет больше не продаётся!");
                    $player->sendTitle("", "§cПредмет не продаётся!", 20, 20, 20);
                    return;
                }
                $ownerName = $nbt->owner->getValue();
                $currentTime = time();
                if($ownerName === $player->getLowerCaseName()){
                    if(($this->confirmations[$player->getName()][$uniqueKey] ?? 0) < $currentTime){
                        $this->confirmations[$player->getName()][$uniqueKey] = $currentTime + 8;
                        $player->sendPopup("§aНажми на предмет ещё раз!");
                        return;
                    }
                    $inventory->setItem($_transaction->getSlot(), new \pocketmine\item\Item(\pocketmine\block\BlockIds::AIR));
                    \Richen\Engine\Auction\Auction::getInstance()->pullFromTheAuction($player, $uniqueKey);
                    unset($this->confirmations[$player->getName()][$uniqueKey]);
                    return;
                }
                if($nbt->expirationDate->getValue() < \Richen\Engine\Auction\Helper::breakTime()){
                    $player->sendMessage("§a➛ §cЭтот предмет больше не продаётся!");
                    $player->sendTitle("", "§cВремя вышло", 20, 20, 20);
                    return;
                }
                $price = $nbt->price->getValue();
                $cash = $this->core()->cash();
                $playerMoney = $cash->getUserCash($player->getName())->getMoney();
                if ($playerMoney < $price) {
                    $notEnough = number_format($price - $playerMoney);
                    $player->sendMessage("§a➛ §cТебе не хватает §b" . $notEnough . '$');
                    return;
                }
                if(!$player->getInventory()->canAddItem($item)){
                    $player->sendMessage("§a➛ §cУ тебя нет места в инвентаре!");
                    return;
                }
                if(($this->confirmations[$player->getName()][$uniqueKey] ?? 0) < $currentTime){
                    $this->confirmations[$player->getName()][$uniqueKey] = $currentTime + 12;
                    $player->sendPopup("§aНажми ещё раз для покупки!");
                    return;
                }
                $inventory->setItem($_transaction->getSlot(), new \pocketmine\item\Item(\pocketmine\block\BlockIds::AIR));
                \Richen\Engine\Auction\Auction::getInstance()->pullFromTheAuction($player, $uniqueKey, true);
                unset($this->confirmations[$player->getName()][$uniqueKey]);
                return;
            }
        }
    }

    public function onMove(\pocketmine\event\player\PlayerMoveEvent $ev) {
        $player = $ev->getPlayer();
        $this->core()->shop()->checkForCloseShop($player);
        
    }

    public function valid($player, bool $auth = true): bool {
        return $player instanceof NBXPlayer && (!$auth || ($player->isauth() && $player->isreg()));
    }

    # config verify

    public function onInventory(\pocketmine\event\entity\EntityInventoryChangeEvent $ev) {
        $player = $ev->getEntity();
        if (!$this->valid($player)) {
            $ev->setCancelled();
            return;
        }
        $warps = $this->core()->getServerData()['warps'] ?? [];
        if (count($warps)) {
            $name = $ev->getNewItem()->getCustomName();
            foreach ($warps as $warp => $data) {
                if ($name === $data['item']['name']) {
                    $ev->setCancelled();
                    $pos = Utils::strToPosition($data['position']);
                    if ($pos) {
                        $player->teleport($pos);
                        $player->sendMessage('Вы телепортированы на варп: ' . mb_strtoupper($warp));
                    } else {
                        $player->sendMessage('Точка установлена не верно');
                    }
                    break;
                }
            }
        }
    }

	public function handleInventoryPickupArrow(\pocketmine\event\inventory\InventoryPickupArrowEvent $event){
		$arrow = $event->getArrow();
		if (isset($arrow->namedtag->infinite)) {
			$event->setCancelled(true);
		}
	}

    private function addSuccessfulBowHitSound(\pocketmine\Player $player) : void{
		$pk = new class extends \pocketmine\network\mcpe\protocol\PlaySoundPacket {
			public function decode(){
				$this->sound = $this->getString();
				$this->getBlockCoords($this->x, $this->y, $this->z);
				$this->x /= 8;
				$this->y /= 8;
				$this->z /= 8;
				$this->volume = $this->getLFloat();
				$this->float = $this->getLFloat();
			}

			public function encode(){
				$this->reset();
				$this->putString($this->sound);
				$this->putBlockCoords((int)($this->x * 8), (int)($this->y * 8), (int)($this->z * 8));
				$this->putLFloat($this->volume);
				$this->putLFloat($this->float);
			}
		};
		$pk->sound = 'random.orb';
		$pk->x = $player->getFloorX();
		$pk->y = $player->getFloorY();
		$pk->z = $player->getFloorZ();
		$pk->float = 0.5;
		$pk->volume = 1.0;

		$player->dataPacket($pk);
	}

    private function addHurtAnimation(\pocketmine\Player $player): void {
		$pk = new \pocketmine\network\mcpe\protocol\EntityEventPacket();
		$pk->eid = $player->getId();

		if ($player->spawned and $player->isAlive()) {
			$pk->event = \pocketmine\network\mcpe\protocol\EntityEventPacket::HURT_ANIMATION;
			$player->dataPacket($pk);
		}

		$pk->event = $player->getHealth() <= 0 ? \pocketmine\network\mcpe\protocol\EntityEventPacket::DEATH_ANIMATION : \pocketmine\network\mcpe\protocol\EntityEventPacket::HURT_ANIMATION;
		$this->serv()->broadcastPacket($player->getViewers(), $pk);
	}
    
    public function onDamage(\pocketmine\event\entity\EntityDamageEvent $event) {
        $entity = $event->getEntity();
        $damage = $event->getDamage();
        
        $x = $entity->getFloorX();
        $y = $entity->getFloorY();
        $z = $entity->getFloorZ();

        if ($entity instanceof \pocketmine\Player && $entity->getLevel()->getName() === 'world') {
            $message = Utils::getCause($event->getCause(), $entity);
            $mn = $this->core()->rgns();
            $rg = $mn->getRegionByPos($x, $y, $z, $entity->getLevel()->getName());
            if ($rg->isRegion()) {
                if (!$rg->getFlag('damage')) {
                    $event->setCancelled();
                    return;
                }
            }
        }
        
        if ($event instanceof \pocketmine\event\entity\EntityDamageByChildEntityEvent && $entity instanceof NBXPlayer) {
            $arrow = $event->getChild();
            if ($arrow instanceof \pocketmine\entity\Arrow && !$arrow->isClosed() && $arrow->isAlive()){
                if ($arrow->getPotionId() === 36) {
                    $entity->setLastDamageCause(new \pocketmine\event\entity\EntityDamageByEntityEvent($event->getDamager(), $entity, \pocketmine\event\entity\EntityDamageEvent::CAUSE_PROJECTILE, 1));
                    $entity->setAbsorption(0);
                    $entity->setHealth($entity->getHealth() - rand(1, 1));
                    $this->addHurtAnimation($entity);
                }
                if (isset($arrow->namedtag->power)) {
                    $event->setDamage($event->getDamage() + $arrow->namedtag->power->getValue());
                }
                if (isset($arrow->namedtag->knockback)) {
                    $event->setKnockback($event->getKnockback() + $arrow->namedtag->knockback->getValue());
                }
                $damager = $event->getDamager();
                if ($damager instanceof NBXPlayer) {
                    $this->addSuccessfulBowHitSound($damager);
                }
            }
        }

        if ($event instanceof \pocketmine\event\entity\EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            if ($damager instanceof NBXPlayer) {
                if (!$damager->isauth()) {
                    $event->setCancelled();
                    return;
                }
                $event->setDamage($event->getDamage() * $damager->getDamageMultiplier());
                if ($damager->getPlatform() === NBXPlayer::PC) {
                    //$event->setKnockBack(($event->getKnockBack() / 100) * 120);
                }
                
                if ($entity instanceof \pocketmine\entity\LavaSlime) {
                    $this->core()->shop()->onSelectMenuItem($entity, $damager);
                    //$this->core()->help()->onSelectMenuItem($entity, $damager);
                    return;
                }

                $mn = $this->core()->rgns();
                if ($entity instanceof NBXPlayer) {
                    $rg = $mn->getRegionByPos($x, $y, $z, $entity->getLevel()->getName());
                    if ($entity->getLevel()->getName() === 'world' && $rg->isRegion()) {
                        if (!$rg->getFlag('pvp')) {
                            $damager->sendTip('§cПВП на этой территории отключено');
                            $event->setCancelled();
                            return;
                        }
                    }
                    if ($damager->getGamemode() === \pocketmine\Player::CREATIVE) {
                        if ($entity->getGamemode() === \pocketmine\Player::SURVIVAL) {
                            $event->setCancelled();
                            $damager->sendTip('§cВы не можете драться в креативе');
                            return;
                        }
                    } elseif ($damager->getGamemode() === \pocketmine\Player::SURVIVAL) {
                        if ($damager->getInventory()->getItemInHand()->getId() !== \pocketmine\item\Item::BOW) {
                            if ($damager->distance($entity) > 3.9) {
                                $event->setCancelled();
                                return;
                            }
                            if ($damager->getScale() !== $entity->getScale()) {
                                $damager->sendTip('§cНельзя наносить урон игроку с другим ростом');
                                $event->setCancelled();
                                return;
                            }
                            if ($entity->isSleeping()) {
                                $damager->sendTip('§cИгрок спит, урон ограничен');
                                $event->setDamage(0.5);
                                return;
                            }
                        }
                    }
                    $message = Utils::getCause($event->getCause(), $entity, $damager);
                    $this->startPVP($entity);
                    $this->startPVP($damager);
                }
            } else {
                $message = Utils::getCause($event->getCause(), $entity, null);
                $message = Utils::getCause($event->getCause(), $entity, null, $damager);
            }
        } else if ($entity instanceof NBXPlayer) {
            if (!$entity->isauth()) {
                $event->setCancelled();
                return;
            }
        }
        if (isset($message) && !$event->isCancelled() && $damage >= $entity->getHealth() && $entity instanceof NBXPlayer) {
            $event->setCancelled();
            $this->serv()->broadcastMessage($message);
            $this->respawn($entity);
            $entity->setFight(false);
        }
    }
    public array $inpvp = [];

    public function respawn(\pocketmine\Player $entity) {
        if (!$entity) {
            return;
        }
		$entity->setHealth($entity->getMaxHealth());
		$entity->setFood($entity->getMaxFood());
		$entity->setSaturation(20);
		$entity->sendTitle('§cВы умерли');
		$drops = [];

		foreach ($entity->getInventory()->getContents() as $item) {
			$entity->getLevel()->dropItem($entity->getPosition(), $item, null, 20);
			$drops[] = $item;
		}

		foreach ($entity->getInventory()->getArmorContents() as $armor) {
			$drops[] = $armor;
			$entity->getLevel()->dropItem($entity->getPosition(), $armor, null, 20);
		}

		// foreach ($entity->getOffHandInventory()->getContents() as $content) {
		// 	$drops[] = $content;
		// 	$entity->getLevel()->dropItem($entity->getPosition(), $content, null, 20);
		// }

		if ($entity->isConnected()) {
			//$entity->teleport($this->core()->getAPI()->getLobbyPosition());
		}
		$entity->getInventory()->clearAll();
		$entity->extinguish();
    }

    public function onQuit(\pocketmine\event\player\PlayerQuitEvent $ev) {
        $ev->setQuitMessage(null);
        $player = $ev->getPlayer();
        if (!$player instanceof NBXPlayer) return;
        $player->logout();
        
		\Richen\Engine\Auction\Auction::getInstance()->removeBlockAndTile($player);
		unset($this->confirmations[$player->getName()]);
        $this->core()->shop()->checkForCloseShop($player);
        
        $this->core()->help()->removeEntities($player);
        $this->core()->help()->selectPage($player);
        $this->core()->help()->spawnArrows($player);
    }

    private array $online = [];

    public function onBreak(\pocketmine\event\block\BlockBreakEvent $ev) {
        $player = $ev->getPlayer();
        if (!$player instanceof NBXPlayer) return $ev->setCancelled();
        if (!$player->isauth() || !$player->isreg()) return $ev->setCancelled();
    }

    public function onPlace(\pocketmine\event\block\BlockPlaceEvent $ev) {
        $player = $ev->getPlayer();
        if (!$player instanceof NBXPlayer) return $ev->setCancelled();
        if (!$player->isauth() || !$player->isreg()) return $ev->setCancelled();
    }

    public function onDrop(\pocketmine\event\player\PlayerDropItemEvent $ev) {
        $player = $ev->getPlayer();
        if (!$player instanceof NBXPlayer) return $ev->setCancelled();
        if (!$player->isauth() || !$player->isreg()) return $ev->setCancelled();
        $warps = $this->core()->getServerData()['warps'] ?? [];
        $name = $ev->getItem()->getCustomName();
        foreach ($warps as $warp => $data) {
            if ($name === $data['item']['name']) {
                $ev->setCancelled();
                break;
            }
        }
        $item = $ev->getItem();
		if ($item->hasCompoundTag()) {
            if (isset($item->getNamedTag()->auctionWindowItem)) {
                $ev->setCancelled(true);
            }
        }
    }

    public function handleEntityShootBow(\pocketmine\event\entity\EntityShootBowEvent $event){
		if($event->isCancelled()){
			return;
		}
		$player = $event->getEntity();
		if(!$player instanceof \pocketmine\Player){
			return;
		}
		$arrow = $event->getProjectile();
		if(!$arrow instanceof \pocketmine\entity\Arrow){
			return;
		}
		$bow = $event->getBow();
		if($bow->hasEnchantment(\pocketmine\item\enchantment\Enchantment::TYPE_BOW_INFINITY)){
			if($arrow->getPotionId() > 0){
				$event->setCancelled(true);
				$player->sendMessage('§7> §cЛук на бесконечность нельзя использовать §bсо стрелами с эффектами§c!');
				return;
			}
			if($player->isSurvival()){
				$index = $player->getInventory()->first(\pocketmine\item\Item::get(\pocketmine\item\ItemIds::ARROW, -1));
				if($index !== -1){
					$item = $player->getInventory()->getItem($index);
					$item->setCount(1);
					$player->getInventory()->addItem($item);
				}
			}
			$arrow->namedtag->infinite = new \pocketmine\nbt\tag\ByteTag('infinite', 1);
		}
		if($bow->hasEnchantment($id = \pocketmine\item\enchantment\Enchantment::TYPE_BOW_POWER)){
			$arrow->namedtag->power = new \pocketmine\nbt\tag\FloatTag('power', ($bow->getEnchantmentLevel($id) * ($player->getMaxHealth() > 20 ? 2 : 1.5)));
		}
		if($bow->hasEnchantment($id = \pocketmine\item\enchantment\Enchantment::TYPE_BOW_KNOCKBACK)){
			$arrow->namedtag->knockback = new \pocketmine\nbt\tag\FloatTag('knockback', ($bow->getEnchantmentLevel($id) * (rand(0, 1) !== 0 ? 0.03 : 0.04)));
		}
		if($bow->hasEnchantment($id = \pocketmine\item\enchantment\Enchantment::TYPE_BOW_FLAME)){
			$arrow->setOnFire($bow->getEnchantmentLevel($id) * 25);
		}
		(function() : void{
			$potionId = $this->{'potionId'};
			if($potionId === 23 or $potionId === 24){
				$this->{'potionId'} = 0;
			}
		})->call($arrow);
		$event->setProjectile($arrow);
	}

    public function handlePlayerItemHeld(\pocketmine\event\player\PlayerItemHeldEvent $event){
		if($event->isCancelled()){
			return;
		}
		$player = $event->getPlayer();
		$item = $event->getItem();
		if(!$item->hasCompoundTag()){
			return;
		}
		if(!isset($item->getNamedTag()->auctionWindowItem)){
			return;
		}
		$player->getInventory()->setItemInHand(new \pocketmine\item\Item(\pocketmine\block\BlockIds::AIR));
	}

    public function onRecieve(\pocketmine\event\server\DataPacketReceiveEvent $ev) {
        $player = $ev->getPlayer();
        $packet = $ev->getPacket();
        if ($packet instanceof \pocketmine\network\mcpe\protocol\LoginPacket) {
            if ($player instanceof NBXPlayer) {
                $inputMode = isset($packet->clientData['CurrentInputMode']) ? $packet->clientData['CurrentInputMode'] : 0;
                $player->setPlatform(min(NBXPlayer::GAMEPAD, $inputMode));
            }
            $deviceOS = (int)$packet->clientData['DeviceOS'];
            $deviceModel = (string)$packet->clientData['DeviceModel'];
            $this->serv()->getLogger()->info('Игрок ' . $player->getName() . ' зашел с ' . $deviceModel . ' ' . $deviceOS);
            if ($deviceOS !== 1) return;
            if ($packet->clientId === 0) return $player->close('', '§cНа нашем сервере §l§eToolBox §r§cзапрещен' . PHP_EOL . '§fНаша группа §bВК§f: §d§lvk.com/nubix§r');
            if (strtoupper(explode(' ', $deviceModel)[0]) !== explode(' ', $deviceModel)[0]) return $player->close('', '§c На нашем сервере §l§eToolBox §r§cзапрещен' . PHP_EOL . '§eНаша группа §bВК§f: §d§lvk.com/nubix§r');
        } else if ($packet instanceof \pocketmine\network\mcpe\protocol\InteractPacket) {
            if ($packet->action === \pocketmine\network\mcpe\protocol\InteractPacket::ACTION_LEAVE_VEHICLE) {
                // $sit = $this->core->getAPI()->sit;
                // if (isset($sit->sittingPlayers[$playerName = $player->getName()]) && $packet->target === $sit->sittingPlayers[$playerName]) {
                //     $sit->standUp($player);
                // }
            }
        }
    }

    // public function onChangeLevel(\pocketmine\event\entity\EntityLevelChangeEvent $event) {
    //     $entity = $event->getEntity();
    //     if ($entity instanceof Player) {
    //         $entity->getServer()->getScheduler()->scheduleDelayedTask(new class($entity, $this->core, $event) extends \pocketmine\scheduler\Task {
    //             public $entity, $core, $event;
    //             public function __construct($entity, $core, $event) {
    //                 $this->entity = $entity;
    //                 $this->core = $core;
    //                 $this->event = $event;
    //             }
    //             public function onRun($currentTick) {
    //                 if ($this->event->getTarget()->getName() !== 'lobby') {
    //                     foreach(FloatingText::$idfloating as $id) {
    //                         FloatingText::removeCustomFloating($this->entity, $id);
    //                         $this->core->shop->removeEntities($this->entity);
    //                         $this->core->help->removePage($this->entity);
    //                     }
    //                 } else {
    //                     $this->core->help->removeEntities($this->entity);
    //                     $this->core->help->selectPage($this->entity);
    //                     $this->core->help->spawnArrows($this->entity);
    //                     $this->core->shop->spawnShopForPlayer($this->entity);
    //                 }
    //             }
    //         }, 1 * 20);
    //     }
    // }

	public function onQuery(\pocketmine\event\server\QueryRegenerateEvent $ev) {
        //$event->setPlayerCount();
		$ev->setMaxPlayerCount(count($this->serv()->getOnlinePlayers()) + 1);
	}
    
    public function onTouch(\pocketmine\event\player\PlayerInteractEvent $ev) {
        $player = $ev->getPlayer();
        if (!$player instanceof NBXPlayer) return $ev->setCancelled();
        if (!$player->isauth() || !$player->isreg()) return $ev->setCancelled();
		$item = $ev->getItem();
        if ($item->hasCompoundTag()) {
		    if(isset($item->getNamedTag()->auctionWindowItem)){
    			$player->getInventory()->setItemInHand(new \pocketmine\item\Item(\pocketmine\block\BlockIds::AIR));
		    }
        }
        $this->core()->shop()->onChestClick($ev);
    }

    public function handlePlayerCraftItem(\pocketmine\event\inventory\CraftItemEvent $ev){
		foreach($ev->getInput() as $item){
			if(!$item->hasCompoundTag()){
				continue;
			}
			if(!isset($item->getNamedTag()->auctionWindowItem)){
				continue;
			}
			$ev->setCancelled(true);
			return;
		}
	}

    public function onLogin(\pocketmine\event\player\PlayerLoginEvent $ev) {
        $player = $ev->getPlayer();
    }

    public function onOpen(\pocketmine\event\inventory\InventoryOpenEvent $e) {
        if ($e->getInventory() instanceof \pocketmine\inventory\EnchantInventory || $e->getInventory() instanceof \pocketmine\inventory\AnvilInventory) { $e->getPlayer()->onRename(); }
    }

    public function onClose(\pocketmine\event\inventory\InventoryCloseEvent $ev) {
        $player = $ev->getPlayer();
        if (\Richen\Engine\Auction\Auction::getInstance()->isViewingAuction($player = $ev->getPlayer())) {
            if ($ev->getInventory() instanceof \Richen\Engine\Additions\PersonalDoubleInventory) {
                \Richen\Engine\Auction\Auction::getInstance()->addToDelayedClose($player);
            }
		}
        if ($ev->getInventory() instanceof \pocketmine\inventory\AnvilInventory || $ev->getInventory() instanceof \pocketmine\inventory\EnchantInventory) {
            $this->serv()->getScheduler()->scheduleRepeatingTask(new class($player) extends \pocketmine\scheduler\Task { public $pl; public function __construct(\pocketmine\Player $pl) { $this->pl = $pl; } public function onRun($tick) { $this->pl->onRename(false); } }, 10);
        } 
    }

    public function onJoin(\pocketmine\event\player\PlayerJoinEvent $ev) {
        $ev->setJoinMessage(null);
        $player = $ev->getPlayer();
		$player->setScale(1.0);
        $player->setImmobile(true);
        $player->addEffect(Effect::getEffect(Effect::BLINDNESS)->setDuration(20 * 60)->setVisible(false));
        if (!$player instanceof NBXPlayer) return;
        $this->online[$player->getName()] = time();
        $lang = $this->core()->lang();
        $player->title($lang->prepare('auth-title-join'), $lang->prepare('auth-subtitle-join'), 20, 20, 40, 20);
        if ($player->identification()) {
            $player->setImmobile(false);
            $player->sendMessage($lang->prepare('auth-can-auto-login', $lang::SUC));
            $player->login(true);
        } else {
            $player->sendMessage($lang->prepare('auth-welcome', $lang::WRN, [$player->getName()]));
            if (!$player->isreg()) {
                $player->sendMessage($lang->prepare('auth-need-reg', $lang::WRN));
            } elseif (!$player->isauth()) {
                $player->sendMessage($lang->prepare('auth-need-auth', $lang::WRN));
            }
            $this->core()->getServer()->getScheduler()->scheduleRepeatingTask(new class($player) extends \pocketmine\scheduler\Task {
                protected NBXPlayer $player; protected int $counter = 0; public function __construct(NBXPlayer $player) { $this->player = $player; }
                public function onRun($tick) {
                    $player = $this->player;
                    if ($this->counter >= 8) { $this->getHandler()->remove(); return $this->player->close('', $this->player->core()->lang()->prepare('auth-time-out-kick')); }
                    if ($player->isauth() && $player->isreg()) return $this->getHandler()->remove();
                    $this->counter++;
                    if (!$player->isreg()) { $player->sendTitle('', $player->core()->lang()->prepare('auth-subtitle-need-reg'), 20, 60, 20); return; }
                    if (!$player->isauth()) { $player->sendTitle('', $player->core()->lang()->prepare('auth-subtitle-need-reg'), 20, 60, 20); return; }
                    $player->sendTitle('', $player->core()->lang()->prepare('auth-need-auth'), 20, 60, 20);
                }
            }, 20 * 5);
        }
        $this->core()->shop()->spawnShopForPlayer($player);
        $this->core()->stat()->spawnTopsForPlayer($player);
        $this->core()->help()->selectPage($player);
        $this->core()->help()->spawnArrows($player);
        foreach ($this->serv()->getOnlinePlayers() as $player) {
            if (!$player instanceof NBXPlayer) continue;
			if ($player->isSit()) {
				$this->sendSittingPackets($ev->getPlayer(), $player, $player->getSitId());
			} else {
                $player->unsetSit();
            }
		}
    }

    public function sendSittingPackets(NBXPlayer $viewer, NBXPlayer $rider, int $eid) {
		$pk = new \pocketmine\network\mcpe\protocol\AddEntityPacket();
		$pk->eid = $eid;
		$pk->type = 84;
		$pk->x = $rider->getFloorX() + 0.5;
		$pk->y = $rider->y - ($rider->getScale() >= 1.0 ? 0.12 : -11.65);
		$pk->z = $rider->getFloorZ() + 0.5;
		$pk->metadata = [
			\pocketmine\entity\Entity::DATA_FLAGS => [\pocketmine\entity\Entity::DATA_TYPE_LONG, (1 << \pocketmine\entity\Entity::DATA_FLAG_IMMOBILE) | (1 << \pocketmine\entity\Entity::DATA_FLAG_INVISIBLE)]
		];
		$pk->speedX = $pk->speedY = $pk->speedZ = $pk->yaw = $pk->pitch = 0;

		$viewer->dataPacket($pk);

		$pk2 = new \pocketmine\network\mcpe\protocol\SetEntityLinkPacket();
		$pk2->from = $pk->eid;
		$pk2->to = $rider->getId();
		$pk2->type = 1;

		$viewer->dataPacket($pk2);
	}

    public int $pvpinterval = 15;
    public function startPVP(NBXPlayer $player) {
        if ($player->inFight()) {
            $player->setFight($this->pvpinterval);
        } else {
            $this->serv()->getScheduler()->scheduleRepeatingTask(new class($player) extends \pocketmine\scheduler\Task {
                private $player; public function __construct(NBXPlayer $player) { $this->player = $player; }
                public function onRun($currentTick): void {
                    $pl = $this->player;
                    if (!$pl->isOnline()) { $this->getHandler()->cancel(); return; }
                    if (!$pl->inFight()) {
                        $pl->sendMessage('§6[!] §eАктивирован §cPVP режим§e. Не выходите из игры во время боя');
                        $pl->sendTitle('', '§eБой начался, вы в §сPVP режиме', 10, 20, 20);
                        $pl->getLevel()->addSound((new \pocketmine\level\sound\GhastShootSound($pl)), [$pl]);
                    } else {
                        $bb = \Richen\Engine\Additions\BossBar::getInstance();
                        $bb->setTitle('§6ВЫ В §cPVP §6РЕЖИМЕ' . PHP_EOL . PHP_EOL . '§eДо завершения §cPVP §e- §6§l' . $pl->getFight() . 'сек', $bb->getEid($pl->getLowerCaseName()));
                        $bb->setPercentage((int) 100 / 15 * $pl->getFight(), $bb->getEid($pl->getLowerCaseName()));
                        $pl->setFight($pl->getFight() - 1);
                    }
                    if ($pl->getFight() <= 0) {
                        $this->getHandler()->cancel();
                        $pl->sendMessage('§6[!] §eВы вышли из §cPVP режима§e, бой окончен');
                        $pl->sendTitle('', '§eБой окончен', 10, 30, 20);
                        $pl->setFight(0);
                    }
                }
            }, 20);
        }
    }

    private array $register = [];
    public function checkRegister(NBXPlayer $player, $pass) {
        $lang = $this->core()->lang();
        if (!$player->isreg()) {
            $hash = Utils::hash($pass, $player->getName());
            if (isset($this->register[$player->getName()])) {
                if ($this->register[$player->getName()] === $hash) {
                    if (($id = $this->core()->user()->register($player->getName(), $hash, $player->getAddress())) > 0) {
                        $player->sendMessage($lang->prepare('auth-success-register', $lang::SUC));
                        $this->serv()->broadcastMessage($lang->prepare('auth-register-count-info', $lang::SUC, [$player->getName(), $id]));
                        $player->login();
                    } else {
                        $player->sendMessage($lang->prepare('auth-unknown-error', $lang::ERR));
                    }
                } else {
                    $player->sendMessage($lang->prepare('auth-error-repeat-password', $lang::ERR));
                }
                unset($this->register[$player->getName()]);
            } else {
                if (mb_strlen($pass) < 6) return $player->sendMessage($lang->prepare('auth-error-min-length', $lang::ERR, [6]));
                if (mb_strlen($pass) > 16) return $player->sendMessage($lang->prepare('auth-error-max-length', $lang::ERR, [16]));
                if (!preg_match("/^[a-zA-Z0-9]{6,16}$/", $pass)) return $player->sendMessage($lang->prepare('auth-error-invalid-lang', $lang::ERR));
                $this->register[$player->getName()] = $hash;
                $player->sendMessage($lang->prepare('auth-need-repeat-password', $lang::WRN));
            }
        } elseif (!$player->isauth()) {
            $hash = Utils::hash($pass, $player->getName());
            if ($hash === $player->getPassword()) {
                $player->sendMessage($lang->prepare('auth-success-login', $lang::SUC));
                $player->login(true);
            } else {
                $player->sendMessage($lang->prepare('auth-incorrect-password', $lang::ERR));
            }
        } else {

        }
    }
    
    private int $maxCommands = 1, $timeFrame = 2;
    private array $lastMessage = [],  $commandCounts = [], $lastCommandTimes = [];
    const GLOB = 0, LOCL = 1, CLAN = 2, WISP = 3, STAF = 4;

    public function prepareSmiles(string $message) { return str_replace([':)', ':(', '<3'], ['§a☺§f', '§c☹§f', '§c❤§f'], $message); }
    private function isDublicateMessage(string $nick, string $message): bool { return isset($this->lastMessage[$nick]) && $this->lastMessage[$nick] === $message; }
    private function isCommandLimitReached(string $nick): bool { if (time() - ($this->lastCommandTimes[$nick] ?? 0) >= $this->timeFrame) $this->commandCounts[$nick] = 0; return ($this->commandCounts[$nick] ?? 0) >= $this->maxCommands; }
    private function isCooldownActive(string $nick): bool { return time() - ($this->lastCommandTimes[$nick] ?? 0) < $this->timeFrame; }
    public function onPlayerChat(\pocketmine\event\player\PlayerChatEvent $ev) {
        $player = $ev->getPlayer();
        if (!$player instanceof NBXPlayer) return $ev->setCancelled();
        $message = $ev->getMessage();
        if (is_string(($result = $this->antiSpam($player, $message)))) { $player->sendMessage($result); return $ev->setCancelled(); }
        $message = $this->prepareSmiles($message);
        $filter = \Richen\Engine\Filter::getFiltered($message);
        if (!$this->hasPermission($player, 'chat.color')) $message = preg_replace('/§[0-9a-fklmnor]/i', '', $message);
        switch ($message[0]) { case '!': $type = self::GLOB; break; case '#': $type = self::WISP; break; case '@': $type = self::CLAN; break; case '>': $type = self::STAF; break; default: $type = self::LOCL; break; }
        if ($type !== self::LOCL) $filter['message'] = mb_substr($filter['message'], 1);
        if (!$filter['isallowed']) $player->sendMessage($this->lang()->prepare('chat-not-swear', $this->lang()::ERR));
        $format = $player->formatMessage(trim($filter['message']));
        switch ($type) {
            case self::GLOB: $prefix = '§6Ⓖ§r '; break;
            case self::WISP: $radius = 10; $prefix = '§7Шепотом:§r '; break;
            case self::CLAN: $prefix = ''; break;
            case self::LOCL: $radius = 100; $prefix = '§7Ⓛ§r '; break;
            case self::STAF: $prefix = ''; break;
        }
        $recipients = [];
        foreach ($ev->getRecipients() as $recipient) {
            if (isset($radius) && $recipient instanceof \pocketmine\Player && !$this->serv()->isOp($player->getName())) {
                if ($player->getPosition()->distance($recipient->getPosition()) <= $radius) {
                    if ($type === self::LOCL && !$this->serv()->isOp($recipient->getName())) {
                        if ($recipient->getLevel()->getFolderName() === $player->getLevel()->getFolderName()) {
                            $recipients[] = $recipient;
                        }
                    } else {
                        $recipients[] = $recipient;
                    }
                }
            } elseif ($type === self::CLAN) {
                $ev->setCancelled();
                $clan = $player->getClan();
                if (!$clan) return $player->sendMessage('§4[!] §cВы не можете пользоваться клановым чатом, вы не состоите в клане');
                $this->core()->clan()->broadcastMessage($clan, trim($filter['message']), $player->getName());
                return;
            } elseif ($type === self::STAF) {
                if (!$player->hasPermission('admin.chat')) {
                    return $player->sendMessage($this->lang()::ERR . ' §cУ вас нет прав для использования админ чата');
                }
                if ($recipient->hasPermission('admin.chat')) {
                    $recipients[] = $recipient;
                    $prefix = '§6[§cАдмин§7-§fЧат§6] §r';
                }
            } else {
                $recipients[] = $recipient;
            }
        }
        $ev->setFormat($prefix . $format);
        $ev->setRecipients($recipients);
    }

    protected $minLength = 3;
    public function antiSpam(NBXPlayer $player, string $message) {
        if ($this->hasPermission($player, 'chat.antispam')) return true;
        if ($this->isDublicateMessage($player->getName(), $message) && $message[0] !== '/') return $this->lang()->prepare('chat-not-duplicate', $this->lang()::ERR);
        if ($this->isCommandLimitReached($player->getName())) return $this->lang()->prepare('chat-send-so-fast', $this->lang()::ERR);
        if ($this->isCooldownActive($player->getName())) return $this->lang()->prepare('chat-send-so-fast', $this->lang()::ERR);
        if (mb_strlen($message) < 3) return $this->lang()->prepare('chat-min-length-message', $this->lang()::ERR, [$this->minLength]);
        $this->lastMessage[$player->getName()] = $message;
        $this->lastCommandTimes[$player->getName()] = time();
        $this->commandCounts[$player->getName()]++;
        return true;
    }

    public function onCommandPreprocess(\pocketmine\event\player\PlayerCommandPreprocessEvent $ev) {
        $player = $ev->getPlayer();
        if (!$player instanceof NBXPlayer) return $ev->setCancelled();
        $message = $ev->getMessage();
        $iscmd = $message[0] === '/';
        if (!$iscmd) {
            if (!$player->isreg() || !$player->isauth()) { $ev->setCancelled(); return $this->checkRegister($player, explode(' ', $message)[0]); }
        } else {
            if (!$player->isauth()) return $ev->setCancelled();
            if (is_string(($result = $this->antiSpam($player, $message)))) { $player->sendMessage($result); return $ev->setCancelled(); }
        }
    }

    
}