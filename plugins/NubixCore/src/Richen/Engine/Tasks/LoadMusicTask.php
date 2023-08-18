<?php 
namespace Richen\Engine\Tasks;
class LoadMusicTask extends AsyncTaskManager { public function onCompletion(\pocketmine\Server $server){ \Richen\Engine\Additions\Music::getInstance()->loadSong(); } public function onRun() {} }
