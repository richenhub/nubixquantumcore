<?php

namespace Richen\Engine\Tasks;

class AutoRestartTask extends TaskManager {
    public $timer = 120;
    public function getTimer() { return $this->timer; }

    public function onRun($tick): void {
        if ($this->timer === 0) {
            $this->getHandler()->cancel();
            $this->core()->serv()->getScheduler()->scheduleRepeatingTask(new CountDown(), 20);
        } elseif ($this->timer % 10 === 0 || $this->timer === 5) {
            $this->core()->serv()->broadcastMessage('§4✧ §7Автоматическая перезагрузка сервера через: §6' . $this->timer . ' мин§7.');
        } else {
            $this->core()->serv()->broadcastTip('§6Перезагрузка сервера через: §e' . $this->timer . ' мин§6.');
        }
        $this->timer -= 5;
    }
}

class CountDown extends TaskManager {
    private $timer = 60;
    public function onRun($tick): void {
        if ($this->timer % 10 === 0 || $this->timer % 15 === 0 || $this->timer <= 5) {
            $this->core()->getServer()->broadcastMessage('§4✧ §7Автоматическая перезагрузка сервера через: §6' . $this->timer . ' сек§7.');
        }
        if ($this->timer <= 15) {
            $this->core()->getServer()->broadcastTip('§6Перезагрузка сервера через: §e' . $this->timer . ' сек§6.');
        }
        if ($this->timer === 1) {
            foreach ($this->core()->getServer()->getOnlinePlayers() as $p) {
                $p->close("", "§a§l☘ §r§7Перезагрузка! Перезайдите!\n§a§l☘ §r§7Группа в ВК: §b§lvk.com/nubix§r");
            }
        } elseif ($this->timer === 0) {
            $this->core()->getServer()->forceShutdown();
        }
        $this->timer -= 1;
    }
}