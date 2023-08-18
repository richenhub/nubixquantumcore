<?php 

namespace Richen\Engine\Tasks;

abstract class AsyncTaskManager extends \pocketmine\scheduler\AsyncTask {
    public static function core(): \Richen\NubixCore { return \Richen\NubixCore::core(); }
}