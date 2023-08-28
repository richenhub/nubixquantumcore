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

use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\scheduler\CallbackTask;
use pocketmine\item\enchantment\Enchantment;

class TicTacMain extends PluginBase
{
    public $table, $tile, $tictac;

    public $economy;

    public function onEnable()
    {
        $this->tile[0][0] = $this->getServer()
                                 ->getDefaultLevel()
                                 ->getTile(new Vector3(308, 75, 108));
        $this->tile[0][1] = $this->getServer()
                                 ->getDefaultLevel()
                                 ->getTile(new Vector3(308, 75, 109));
        $this->tile[0][2] = $this->getServer()
                                 ->getDefaultLevel()
                                 ->getTile(new Vector3(308, 75, 110));
        $this->tile[1][0] = $this->getServer()
                                 ->getDefaultLevel()
                                 ->getTile(new Vector3(308, 76, 108));
        $this->tile[1][1] = $this->getServer()
                                 ->getDefaultLevel()
                                 ->getTile(new Vector3(308, 76, 109));
        $this->tile[1][2] = $this->getServer()
                                 ->getDefaultLevel()
                                 ->getTile(new Vector3(308, 76, 110));
        $this->tile[2][0] = $this->getServer()
                                 ->getDefaultLevel()
                                 ->getTile(new Vector3(308, 77, 108));
        $this->tile[2][1] = $this->getServer()
                                 ->getDefaultLevel()
                                 ->getTile(new Vector3(308, 77, 109));
        $this->tile[2][2] = $this->getServer()
                                 ->getDefaultLevel()
                                 ->getTile(new Vector3(308, 77, 110));

        $this->economy = $this->getServer()
                              ->getPluginManager()
                              ->getPlugin("EconomyAPI");
        $this->getServer()
             ->getPluginManager()
             ->registerEvents(new TicTacListener($this), $this);

        $this->restartGame();
    }

    

    /* есть ли победитель */
    public function checkIfWinner()
    {
        if($this->table[0][0] == $this->table[1][0] && $this->table[1][0] == $this->table[2][0] && ($this->table[0][0] == 'x' || $this->table[0][0] == 'o')) {
            return true;
        } elseif($this->table[0][1] == $this->table[1][1] && $this->table[1][1] == $this->table[2][1] && ($this->table[0][1] == 'x' || $this->table[0][1] == 'o')) {
            return true;
        } elseif($this->table[0][2] == $this->table[1][2] && $this->table[1][2] == $this->table[2][2] && ($this->table[0][2] == 'x' || $this->table[0][2] == 'o')) {
            return true;
        } elseif($this->table[0][0] == $this->table[0][1] && $this->table[0][1] == $this->table[0][2] && ($this->table[0][0] == 'x' || $this->table[0][0] == 'o')) {
            return true;
        } elseif($this->table[1][0] == $this->table[1][1] && $this->table[1][1] == $this->table[1][2] && ($this->table[1][0] == 'x' || $this->table[1][0] == 'o')) {
            return true;
        } elseif($this->table[2][0] == $this->table[2][1] && $this->table[2][1] == $this->table[2][2] && ($this->table[2][0] == 'x' || $this->table[2][0] == 'o')) {
            return true;
        } elseif($this->table[0][0] == $this->table[1][1] && $this->table[1][1] == $this->table[2][2] && ($this->table[0][0] == 'x' || $this->table[0][0] == 'o')) {
            return true;
        } elseif($this->table[2][0] == $this->table[1][1] && $this->table[1][1] == $this->table[0][2] && ($this->table[2][0] == 'x' || $this->table[2][0] == 'o')) {
            return true;
        } else {
            return false;
        }
    }

    public function startGame()
    {
        $this->tictac["player"][0]->teleport(new Vector3(311, 74, 107));
        $this->tictac["player"][1]->teleport(new Vector3(311, 74, 111));
        $this->getServer()
             ->getScheduler()
             ->scheduleDelayedTask(new CallbackTask([$this, "restartGame"]), 20 * 30);
    }

    public function changeStep()
    {
        if($this->tictac["step"] == 'o') {
            $this->tictac["step"] = 'x';
        } else {
            $this->tictac["step"] = 'o';
        }
        return $this->tictac["step"];
    }

    public function ifCanNotClick($line, $column)
    {
        if(($line > 2 || $column > 2) || ($line < 0 || $column < 0)) {
            return true;
        } elseif($this->table[$line][$column] == 'x' || $this->table[$line][$column] == 'o') {
            return true;
        }
        return false;
    }

    public function checkEmpty()
    {
        for($i = 0; $i < 3; $i++) {
            for($x = 0; $x < 3; $x++) {
                if($this->table[$i][$x] == ' ') {
                    return false;
                }
            }
        }
        return true;
    }

    public function changeTable($set, $line, $column)
    {
        $tile = $this->tile[$line][$column];
        if($tile instanceof \pocketmine\tile\ItemFrame) {
            if($set == 'x') {
                $this->table[$line][$column] = 'x';
                $item = Item::get(30, 0, 1);
                $enchantment = Enchantment::getEnchantment(0);
                $enchantment->setLevel(1);
                $item->addEnchantment($enchantment);
                $tile->setItem($item);
            } else {
                $this->table[$line][$column] = 'o';
                $item = Item::get(378, 0, 1);
                $enchantment = Enchantment::getEnchantment(0);
                $enchantment->setLevel(1);
                $item->addEnchantment($enchantment);
                $tile->setItem($item);
            }
        }
    }

    public function restartGame()
    {
        if($this->tictac["start"] && isset($this->tictac["player"][0])) {
            $this->getServer()
                 ->broadcastMessage("§f[§eКрестики§6Нолики§f] §fУ игроков вышло время.");
            $this->getServer()
                 ->broadcastMessage("§f[§eКрестики§6Нолики§f] §fИгроки §6" . $this->tictac["player"][0]->getName() . " §eи §6" . $this->tictac["player"][1]->getName() . " §eполучают лишь §c80% §eот ставки!");
            $this->getServer()
                 ->broadcastMessage("§f[§eКрестики§6Нолики§f] §fХочешь сыграть тоже? Напиши: §b/ttt ");
            $this->economy->addMoney($this->tictac["player"][0]->getName(), 160);
            $this->economy->addMoney($this->tictac["player"][1]->getName(), 160);
        }

        $this->tictac["start"] = false;
        $this->tictac["step"] = 'o';

        unset($this->tictac["player"][0]);
        unset($this->tictac["player"][1]);
        unset($this->tictac["first"]);

        for($i = 0; $i < 3; $i++) {
            for($x = 0; $x < 3; $x++) {
                $this->table[$i][$x] = ' ';
                $tile = $this->tile[$i][$x];
                $tile->setItem(Item::get(0, 0));
            }
        }
    }
}