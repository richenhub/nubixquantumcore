<?php

namespace Richen\Engine\Additions;

use pocketmine\level\Position;
use pocketmine\level\sound\NoteblockSound;

class Music extends \Richen\Engine\Manager {
    const SONG = 0;
	const NAME = 1;

	public array $songs = []; 
    
    public $index = 0, $song;
    
    public bool $play = false;

    public static $instance;

    public function __construct() {
        self::$instance = $this;
		$this->core()->serv()->getScheduler()->scheduleAsyncTask(new \Richen\Engine\Tasks\LoadMusicTask());
		//if ($this->loadSong()) $this->play = true;
        $this->core()->serv()->getScheduler()->scheduleRepeatingTask(new  \Richen\Engine\Tasks\RunMusicTask(), 2);
    }

    public static function getInstance() { return self::$instance; }

    public function loadSong() {
		$this->songs = [];
		@mkdir($folder = $this->core()->getDataFolder() . 'music/');
		$opendir = opendir($folder);
		while (($file = readdir($opendir)) !== false) {
			if (($pos = mb_stripos($file, '.nbs')) !== false) {
				$this->songs[] = [new MusicPlayer($folder . $file), mb_substr($file, 0, $pos)];
			}
		}
		shuffle($this->songs);
		return true;
	}

	public function playMusic(): string {
		$this->song = clone $this->songs[$this->index][self::SONG];
		return $this->songs[$this->index][self::NAME] . '♫';
	}

	public function playSong() {
		if($this->play){
			if($this->song === null || $this->song->isStop()){
				!isset($this->songs[$this->index + 1]) ? $this->index = 0 : $this->index++;
				$this->playMusic();
			}
			$this->song->onRun();
		}
	}

	public function sendSound($pitch, $type = 0) {
		$level = $this->core()->serv()->getDefaultLevel();
		foreach ($this->core()->serv()->getOnlinePlayers() as $player) {
			$players[] = $player;
		}
		$level->addSound(new NoteblockSound(new Position(148, 67, 219, $level), $type, $pitch), $players);
	}

	public function getPlaySongName() {
		return $this->songs[$this->index][self::NAME] ?? null;
	}
}

class MusicPlayer extends \stdClass {
	private $length, $sounds = [], $tick = 0, $buffer, $offset = 0, $isStop = false;

	public function __construct($path) {
		$fopen = fopen($path, 'r');
		$this->buffer = fread($fopen, filesize($path));
		fclose($fopen);

		$this->length = $this->getShort();
		$height = $this->getShort();
		$this->getString();
		$this->getString();
		$this->getString();
		$this->getString();
		$this->getShort();
		$this->getByte();
		$this->getByte();
		$this->getByte();
		$this->getInt();
		$this->getInt();
		$this->getInt();
		$this->getInt();
		$this->getInt();
		$this->getString();

 		$tick = $this->getShort() - 1;

		while (true) {
			$sounds = [];

			$this->getShort();

			while (true) {
				switch ($this->getByte()) {
					case 1: $type = 4; break;
					case 2: $type = 1; break;
					case 3: $type = 2; break;
					case 4: $type = 3; break;
					default:$type = 0; break;
				}
                
                $pitch = ($this->getByte() - 33) + ($height >= 10 ? $height - 15 : $height);

				$sounds[] = [$pitch, $type];

				if ($this->getShort() === 0) {
					break;
				}
			}
			$this->sounds[$tick] = $sounds;
			if (($jump = $this->getShort()) !== 0) $tick += $jump;
			else break;
		}
	}

	public function onRun() {
		if (!$this->isStop) {
			if (isset($this->sounds[$this->tick])) {
				foreach($this->sounds[$this->tick] as $data) {
					Music::getInstance()->sendSound(...$data);
				}
			}
			if (++$this->tick > $this->length) $this->isStop = true;
		}
	}

	public function isStop() { return $this->isStop; }
	public function get($len) {
		if ($len < 0) { $this->offset = mb_strlen($this->buffer) - 1; return '';
		} elseif ($len === true) { return mb_substr($this->buffer, $this->offset); }
		return $len === 1 ? $this->buffer[$this->offset++] : mb_substr($this->buffer, ($this->offset += $len) - $len, $len);
	}
	public function getByte() { return ord($this->buffer[$this->offset++]); }
	public function getInt() { return (PHP_INT_SIZE === 8 ? unpack('N', $this->get(4))[1] << 32 >> 32 : unpack('N', $this->get(4))[1]); }
	public function getShort() { return unpack('S', $this->get(2))[1]; }
	public function getString() { return $this->get(unpack('I', $this->get(4))[1]); }
}