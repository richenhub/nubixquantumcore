<?php 

namespace Richen\Engine\Tasks;

abstract class TaskManager extends \pocketmine\scheduler\Task {
    abstract public function onRun($tick): void;
    public static function core(): \Richen\NubixCore { return \Richen\NubixCore::core(); }

    public AutoMessageTask $autoMessageTask;
    public AutoRestartTask $autoRestartTask;
    public static array $tasks;
    public static function startTasks() {
        // self::core()->serv()->getScheduler()->scheduleRepeatingTask(new AutoMessageTask(), 20 * 15);
        // self::core()->serv()->getScheduler()->scheduleRepeatingTask(new AutoRestartTask(), 20 * 60 * 5);

        self::$tasks = [
            'automessage' => self::core()->serv()->getScheduler()->scheduleRepeatingTask(new AutoMessageTask(), 20 * 15),
            'bossbar' => self::core()->serv()->getScheduler()->scheduleRepeatingTask(new BossBarTask(), 20),
            'autorestart' => self::core()->serv()->getScheduler()->scheduleRepeatingTask(new AutoRestartTask(), 20 * 60 * 5),
            //'clearlagg' => self::core()->serv()->getScheduler()->scheduleRepeatingTask(new Tasks\ClearLagg($this->core()), 20 * 60 * 10),
        ];
    }
}