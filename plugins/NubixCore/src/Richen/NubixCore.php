<?php 

namespace Richen;

class NubixCore extends \pocketmine\plugin\PluginBase {
    public static NubixCore $core;
    public static NubixData $data;
    public static NubixLang $lang;
    public static NubixUser $user;
    public static NubixCash $cash;
    public static NubixBans $bans;
    public static NubixRgns $rgns;
    public static NubixClan $clan;
    public static $path = '';
    public static \Richen\Engine\Auction\Auction $auc;
    public static \Richen\Engine\Shop\Shop $shop;
    public static \Richen\Engine\Additions\Music $song;
    public static \Richen\Engine\Additions\Stats $stat;
    public static \Richen\Engine\Additions\Help $help;
    public static \Richen\Engine\Additions\Cases $case;
    public function onEnable() {
        self::$path = $this->getDataFolder();
        if (!is_dir(self::$path)) @mkdir(self::$path, 0777, true);
        self::$core = $this;
        self::$data = new NubixData(new \SQLite3($this->getDataFolder() . 'nubix.db'));
        self::$lang = new NubixLang(new \pocketmine\utils\Config($this->getDataFolder() . 'lang.yml', 2));
        self::$user = new NubixUser();
        self::$cash = new NubixCash();
        self::$bans = new NubixBans();
        self::$rgns = new NubixRgns();
        self::$clan = new NubixClan();
        self::$auc  = new \Richen\Engine\Auction\Auction();
        self::$shop = new \Richen\Engine\Shop\Shop();
        self::$song = new \Richen\Engine\Additions\Music();
        self::$stat = new \Richen\Engine\Additions\Stats();
        self::$help = new \Richen\Engine\Additions\Help();
        self::$case = new \Richen\Engine\Additions\Cases();
        $this->launch();
    }

    public function onDisable() {
        
    }

    public function launch() {
        $this->loadServerData();
        $this->registerListeners();
        $this->initCommands();
        \Richen\Engine\Tasks\TaskManager::startTasks();
    }

    public function initCommands2(): void {
        foreach (($cm = $this->getServer()->getCommandMap())->getCommands() as $command) $cm->unregister($command);
        $commands = [];
        foreach (scandir(__DIR__ . '/Commands/') as $file) {
            if ($file === '.' || $file === '..') continue;
            $commandName = basename($file, '.php');
            $commandPath = "\\Richen\\Commands\\$commandName";
            $commands[] = new $commandPath($commandName);
        }
        $cm->registerAll('nbx', $commands);
	}

    public function initCommands(): void {
        $commandMap = $this->getServer()->getCommandMap();
        foreach ($commandMap->getCommands() as $command) $commandMap->unregister($command);
        $commands = [];
        $this->loadCommands(__DIR__ . '/Commands', $commands);
        $commandMap->registerAll('nbx', $commands);
    }

    private function loadCommands($directory, &$commands): void {
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $path = $directory . '/' . $file;
            if (is_dir($path)) {
                $this->loadCommands($path, $commands);
            } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $commandName = pathinfo($file, PATHINFO_FILENAME);
                $commandPath = "\\Richen" . str_replace('/', '\\', mb_substr(dirname($path), mb_strlen(__DIR__))) . "\\$commandName";
                $commands[] = new $commandPath($commandName);
            }
        }
    }

    private array $serverData;
    public function loadServerData() {  @mkdir($this->getDataFolder()); $file = @file_get_contents($this->getDataFolder() . 'serverdata.txt'); $this->setServerData($file ? unserialize($file) : []); }
    public function saveServerData() { file_put_contents($this->getDataFolder() . 'serverdata.txt', serialize($this->serverData)); }
    public function getServerData() { if (!$this->serverData) $this->loadServerData(); return $this->serverData; }
    public function setServerData($data) { $this->serverData = $data; $this->saveServerData(); }

    public function registerListeners() {
        $this->getServer()->getPluginManager()->registerEvents(new Listener\MainListener(), $this);
    }
    public static function conf(string $config, $type = 2): Engine\Config { return new Engine\Config(self::$path . $config); }
    public static function core(): NubixCore { return self::$core; }
    public static function data(): NubixData { return self::$data; }
    public static function lang(): NubixLang { return self::$lang; }
    public static function user(): NubixUser { return self::$user; }
    public static function cash(): NubixCash { return self::$cash; }
    public static function bans(): NubixBans { return self::$bans; }
    public static function rgns(): NubixRgns { return self::$rgns; }
    public static function clan(): NubixClan { return self::$clan; }
    public static function shop(): \Richen\Engine\Shop\Shop { return self::$shop; }
    public static function serv(): \pocketmine\Server { return self::core()->getServer(); }
    public static function stat(): \Richen\Engine\Additions\Stats { return self::$stat; }
    public static function help(): \Richen\Engine\Additions\Help { return self::$help; }
    public static function case(): \Richen\Engine\Additions\Cases { return self::$case; }
}

class ServerData {
    public function construct() {

    }

    public function getAll(): array { return $this->core(); }
}