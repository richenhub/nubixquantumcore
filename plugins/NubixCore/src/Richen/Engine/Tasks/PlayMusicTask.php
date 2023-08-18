<?php 
namespace Richen\Engine\Tasks;
class PlayMusicTask extends AsyncTaskManager { public function onCompletion(\pocketmine\Server $server) { if (\Richen\Engine\Additions\Music::getInstance()->play) \Richen\Engine\Additions\Music::getInstance()->playSong(); } public function onRun() {} }