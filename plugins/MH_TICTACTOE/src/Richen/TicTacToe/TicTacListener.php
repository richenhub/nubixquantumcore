<?php

/**
 *
 *    ███  ███  █      ███  █   █ ████   ███   ███   ███
 *        █     █     █   █ ██  █ █   █ █   █ █     █      ███  █  █
 *     █   ███  █     █████ █ █ █ █   █ █████ █ ██  ███    █ █  █  █
 *     █      █ █   █ █   █ █  ██ █   █ █   █ █   █ █      ██   █  █
 *    ███  ███  ████  █   █ █   █ ████  █   █  ███   ███ █ █ █  ███
 *
 **/

namespace Richen\TicTacToe;

use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerInteractEvent, PlayerQuitEvent};
use pocketmine\math\Vector3;
use pocketmine\event\block\BlockBreakEvent;

class TicTacListener implements Listener
{
    public TicTacMain $plugin;

    public function __construct(TicTacMain $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onBlockBreak(BlockBreakEvent $event)
    {
        $block = $event->getBlock();
        $tile = $event->getPlayer()
                      ->getLevel()
                      ->getTile(new Vector3($block->getX(), $block->getY(), $block->getZ()));
        for($i = 0; $i < 3; $i++) {
            for($x = 0; $x < 3; $x++) {
                if($this->plugin->tile[$i][$x] === $tile) {
                    $event->setCancelled();
                }
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        if(isset($this->plugin->tictac["player"][0])) {
            if(isset($this->plugin->tictac["player"][1]) && $this->plugin->tictac["start"] === true) {
                $this->plugin->tictac["player"][0]->sendMessage("§f[§eКрестики§6Нолики§f] §b" . $this->plugin->tictac["player"][0]->getName() . " §fвышел из игры. Игра окончена.");
                $this->plugin->economy->addMoney($this->plugin->tictac["player"][1]->getName(), 200);
            }
            $this->plugin->restartGame();
        }
    }


    public function onPlayerInteract(PlayerInteractEvent $event)
    {

        $player = $event->getPlayer();
        $x = $event->getBlock()
                   ->getX();
        $y = $event->getBlock()
                   ->getY();
        $z = $event->getBlock()
                   ->getZ();

        if($x == 308 && $y == 74 && $z == 109) {
            $event->setCancelled();
            if($this->plugin->tictac["start"] == false && !isset($this->plugin->tictac["first"])) {

                if($this->plugin->economy->myMoney($player->getName()) < 200) {
                    $player->sendMessage("§f[§eКрестики§6Нолики§f] §fДля игры вам необходимо §a200$" . "§f! Проверить баланс: §f/money");
                    return;
                }

                if(!isset($this->plugin->tictac["player"][0])) {
                    $this->plugin->tictac["player"][0] = $player;
                    $player->sendMessage("§f[§eКрестики§6Нолики§f] §fВы присоединились к очереди! Дождитесь второго игрока! §b(1/2)");
                    return;
                } elseif($this->plugin->tictac["player"][0]->getName() === $player->getName()) {
                    $player->sendMessage("§f[§eКрестики§6Нолики§f] §fВы уже состоите в очереди §b(1/2)");
                    return;
                } elseif(!isset($this->plugin->tictac["player"][1])) {
                    $this->plugin->tictac["player"][1] = $player;
                    $player->sendMessage("§f[§eКрестики§6Нолики§f] §fВы присоединились к игре вторым игроком! §b(2/2)");
                } elseif($this->plugin->tictac["player"][1]->getName() === $player->getName()) {
                    $player->sendMessage("§f[§eКрестики§6Нолики§f] §fВы уже состоите в игре §b(2/2)");
                    return;
                } else {
                    $player->sendMessage("§f[§eКрестики§6Нолики§f] §fИгра уже идёт! Подождите немного.");
                    return;
                }
                $this->plugin->tictac["start"] = true;
                $this->plugin->tictac["first"] = $this->plugin->tictac["player"][mt_rand(0, 1)];

                $this->plugin->tictac["player"][0]->sendMessage("§f[§eКрестики§6Нолики§f] §fПервым ходит: §6" . $this->plugin->tictac["first"]->getName());
                $this->plugin->tictac["player"][1]->sendMessage("§f[§eКрестики§6Нолики§f] §fПервым ходит: §6" . $this->plugin->tictac["first"]->getName());
                $this->plugin->economy->reduceMoney($this->plugin->tictac["player"][0]->getName(), 200);
                $this->plugin->economy->reduceMoney($this->plugin->tictac["player"][1]->getName(), 200);
                $this->plugin->startGame();
                return;
            } else {
                return $player->sendMessage("§f[§eКрестики§6Нолики§f] §fИгра уже идёт! Подождите немного.");
            }
        }

        for($i = 0; $i < 3; $i++) {
            for($k = 0; $k < 3; $k++) {
                if(isset($this->plugin->tile[$i][$k])) {
                    if(($this->plugin->tile[$i][$k]) == ($this->plugin->getServer()
                                                                      ->getDefaultLevel()
                                                                      ->getTile(new Vector3($x, $y, $z)))) {
                        $event->setCancelled();
                        if($this->plugin->tictac["start"] == false) {
                            return;
                        }

                        if($this->plugin->tictac["first"]->getName() != $player->getName()) {
                            return $player->sendMessage("§f[§eКрестики§6Нолики§f] §fСейчас не ваша очередь.");
                        }

                        if($this->plugin->ifCanNotClick($i, $k)) {
                            return $player->sendMessage("§f[§eКрестики§6Нолики§f] §fВы не можете сходить сюда, выберите другое место.");
                        }

                        $c = $this->plugin->changeStep();
                        $this->plugin->changeTable($c, $i, $k);

                        $player->sendMessage("§f[§eКрестики§6Нолики§f] §fВы сходили, теперь ходит противник.");

                        if($this->plugin->checkIfWinner()) {
                            $this->plugin->getServer()
                                         ->broadcastMessage("§f[§eКрестики§6Нолики§f] §fПобедил §b" . $this->plugin->tictac["first"]->getName() . "§f и получил 400$");
                            $this->plugin->getServer()
                                         ->broadcastMessage("§f[§eКрестики§6Нолики§f] §fХочешь сыграть тоже? Напиши: §b§f/ttt");

                            $this->plugin->economy->addMoney($this->plugin->tictac["first"]->getName(), 400);
                            $this->plugin->tictac["start"] = false;

                            return;
                        }

                        if($this->plugin->checkEmpty()) {
                            $this->plugin->getServer()
                                         ->broadcastMessage("§f[§eКрестики§6Нолики§f] §fУ игроков не осталось вариантов ходов.");
                            $this->plugin->getServer()
                                         ->broadcastMessage("§f[§eКрестики§6Нолики§f] §fИгроки §6" . $this->plugin->tictac["player"][0]->getName() . " §fи §6" . $this->plugin->tictac["player"][1]->getName() . " §fполучают лишь §c80% §fот ставки!");
                            $this->plugin->getServer()
                                         ->broadcastMessage("§f[§eКрестики§6Нолики§f] §fХочешь сыграть тоже? Напиши: §f/ttt §fили §f/tictactoe §a☻");
                            $this->plugin->economy->addMoney($this->plugin->tictac["player"][0]->getName(), 160);
                            $this->plugin->economy->addMoney($this->plugin->tictac["player"][1]->getName(), 160);
                            $this->plugin->tictac["start"] = false;
                            return;
                        }

                        if($this->plugin->tictac["first"]->getName() == $this->plugin->tictac["player"][0]->getName()) {
                            $this->plugin->tictac["first"] = $this->plugin->tictac["player"][1];
                            $this->plugin->tictac["first"]->sendMessage("§f[§eКрестики§6Нолики§f] §fПротивник сходил, теперь ваша очередь. Ходите!");
                        } else {
                            $this->plugin->tictac["first"] = $this->plugin->tictac["player"][0];
                            $this->plugin->tictac["first"]->sendMessage("§f[§eКрестики§6Нолики§f] §fПротивник сходил, теперь ваша очередь. Ходите!");
                        }
                    }
                }
            }
        }
    }
}