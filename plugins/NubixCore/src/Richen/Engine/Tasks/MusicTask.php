<?php
namespace Richen\Engine\Tasks;
class LoadMusicTask extends \pocketmine\scheduler\AsyncTask { public function onCompletion(\pocketmine\Server $server){ \Richen\Engine\Additions\Music::getInstance()->loadSong(); } public function onRun() {} }
class PlayMusicTask extends \pocketmine\scheduler\AsyncTask { public function onCompletion(\pocketmine\Server $server) { if (\Richen\Engine\Additions\Music::getInstance()->play) \Richen\Engine\Additions\Music::getInstance()->playSong(); } public function onRun() {} }
class RunMusicTask extends TaskManager { public function onRun($currentTick): void { if (\Richen\Engine\Additions\Music::getInstance()->play) { $this->core()->serv()->getScheduler()->scheduleAsyncTask(new PlayMusicTask()); } } }
