<?php 
namespace Richen\Engine\Tasks;
class RunMusicTask extends TaskManager { public function onRun($currentTick): void { if (\Richen\Engine\Additions\Music::getInstance()->play) { $this->core()->serv()->getScheduler()->scheduleAsyncTask(new PlayMusicTask()); } } }
