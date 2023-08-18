<?php 

namespace Richen\Custom;

use Richen\Engine\Utils;
use Richen\NubixCash;

class PlayerStats {
    private int $user_id;
    private int $kills, $online, $death, $place, $break, $messages;
    private bool $exists = false;
    public function __construct(int $user_id) {
        $res = \Richen\NubixCore::data()->get('stats', [['k' => 'user_id', 'v' => $user_id]]);
        $this->exists = count($res);
        if ($this->exists) $res = $res[0];
        $this->kills = $res['kills'] ?? 0;
        $this->online = $res['online'] ?? 0;
        $this->death = $res['death'] ?? 0;
        $this->place = $res['place'] ?? 0;
        $this->messages = $res['messages'] ?? 0;
        $this->user_id = $user_id;
    }

    public function getKills(): int { return $this->kills; }
    public function getDeath(): int { return $this->death; }
    public function getPlace(): int { return $this->place; }
    public function getBreak(): int { return $this->break; }
    public function getOnline(int $add = 0): int { return $this->online + $add; }
    public function getMessages(): int { return $this->messages; }
    public function addProp(string $prop, int $val): int {
        if (!$this->exists) {
            $res = \Richen\NubixCore::data()->add('stats', ['user_id' => $this->user_id]);
            print_r(\Richen\NubixCore::data()->lastError());
            if ($res > 0) $this->exists = true;
        }
        if ($this->exists && isset($this->$prop)) {
            $this->$prop = $this->$prop += $val;
            \Richen\NubixCore::data()->update('stats', [$prop => $this->$prop], [['k' => 'user_id', 'v' => $this->user_id]]);
        }
        print_r(\Richen\NubixCore::data()->lastError());
        return $this->$prop;
    }
}

class HomeManager {
    private array $homes;
    private NBXPlayer $player;
    public function __construct(NBXPlayer $player) {
        $player->loadCustomData();
        $data = $player->getCustomData();
        $this->homes = $data['homes'] ?? [];
        $this->player = $player;
    }
    public function getHomeList() { return array_keys($this->homes); }
    public function countHomes() { return count($this->homes); }
    public function setHome(string $home, \pocketmine\level\Position $position) { $this->homes[mb_strtolower($home)] = $position->__toString(); $this->save(); }
    public function isHome(string $home): bool { return isset($this->homes[mb_strtolower($home)]); }
    public function delHome(string $home): void { unset($this->homes[mb_strtolower($home)]); $this->save(); }
    public function getHome(string $home): array {
        if (isset($this->homes[mb_strtolower($home)])) {
            $position = $this->homes[mb_strtolower($home)];
            return ['name' => $home, 'position' => Utils::strToPosition($position)];
        }
        return [];
    }
    public function save(): void { $this->player->setCustomData(array_merge($this->player->getCustomData(), ['homes' => $this->homes])); }
}

class LastPosManager {
    private array $positions = [];
    private NBXPlayer $player;
    public function __construct(NBXPlayer $player) {
        $player->loadCustomData();
        $data = $player->getCustomData();
        $this->positions = $data['positions'] ?? [];
        $this->player = $player;
    }
    public function setLastPosition(\pocketmine\level\Position $position) { array_unshift($this->positions, $position->__toString()); foreach($this->positions as $key => $pos) if ($key > 4) unset($this->positions[$key]); $this->save(); }
    public function getLastPosition(): ?\pocketmine\level\Position { $pos = Utils::strToPosition($this->positions[0]); return $pos instanceof \pocketmine\level\Position ? $pos : $this->delLastPosition(); }
    public function delLastPosition() { array_shift($this->positions); }
    public function getPositions() { return $this->positions; }
    public function hasPosition(): bool {
        foreach ($this->positions as $key => $position) {
            if (Utils::strToPosition($position) instanceof \pocketmine\level\Position) return true;
            else unset($this->positions[$key]);
        }
        $this->save();
        return false;
    }
    public function save(): void { $this->player->setCustomData(array_merge($this->player->getCustomData(), ['positions' => $this->positions])); }
}

class Teleport {
    public \pocketmine\level\Position $position;
    public function __construct(\pocketmine\level\Position $position) {
        $this->position = $position;
    }
}

class TeleportManager extends \Richen\Engine\Manager {
    private bool $isTeleport = false;
    private array $requests = [];
    private NBXPlayer $player;
    public function __construct(NBXPlayer $player) { $this->player = $player; }
    public function teleport(\pocketmine\level\Position $position, $loadMsg = '', $tpMsg = '') {
        if ($this->isTeleport()) return $this->player->sendMessage('§4[!] §cВоу воу, подождите пока закончится другая телепортация');
        $this->isTeleport = true;
        $this->serv()->getScheduler()->scheduleRepeatingTask(new \Richen\Engine\Tasks\TeleportTask($this, $this->player, $position, $loadMsg, $tpMsg), 4);
    }
    public function isTeleport() { return $this->isTeleport; }
    public function unsetTeleport() { $this->isTeleport = false; }
    public function getRequests() { return $this->hasRequests() ? $this->requests : []; }
    public function hasRequests() {
        if (empty($this->requests)) return false;
        foreach ($this->requests as $playerName => $time) {
            if ($time < time()) {
                unset($this->requests[$playerName]);
            }
        }
        return !empty($this->requests);
    }
    public function setRequests(NBXPlayer $player) { $this->requests[$player->getName()] = time() + 60; }
    public function unsetRequests() { $this->requests = []; }
}

class NBXPlayer extends \pocketmine\Player {
    use \Richen\Engine\Traits\Helper;
	public function getLowerCaseName(): string { return mb_strtolower($this->iusername); }
    
    public function getHomeManager(): HomeManager { return new HomeManager($this); }
    public function getLastPosManager(): LastPosManager { return new LastPosManager($this); }
    public ?TeleportManager $teleportManager = null;
    public function teleportManager(): TeleportManager { return $this->teleportManager = $this->teleportManager instanceof TeleportManager ? $this->teleportManager : new TeleportManager($this); }

    public function getMaxRegions(): int { return $this->core()->conf('groups')->config()->getAll()[$this->getGroupName()]['maxRegions']; }
    public function getMaxBlocks(): int { return $this->core()->conf('groups')->config()->getAll()[$this->getGroupName()]['maxBlocks']; }

    public function getClan(): ?NBXClan { return $this->core()->clan()->getUserClan($this->getLowerCaseName()); }

    /* Custom data */
    private $customData = [];
    public function loadCustomData() { $file = @file_get_contents($this->core()->getDataFolder() . 'players/' . $this->getLowerCaseName() . '.txt'); $this->setCustomData($file ? unserialize($file) : []); }
    public function saveCustomData() { @mkdir($this->core()->getDataFolder() . 'players/'); file_put_contents($this->core()->getDataFolder() . 'players/' . $this->getLowerCaseName() . '.txt', serialize($this->customData)); }
    public function getCustomData() { if (!$this->customData) $this->loadCustomData(); return $this->customData; }
    public function setCustomData($data) { $this->customData = $data; $this->saveCustomData(); }

    protected bool $isauth = false;
    public function isauth(): bool { return $this->isauth; }
    public function setauth(bool $val): void { $this->isauth = $val; }

    private bool $isreg = false;
    public function isreg(): bool { return $this->isreg; }
    public function setreg(bool $val): void { $this->isreg = $val; }

    protected int $userid = 0;
    public function getuserid(): int { return $this->userid = $this->userid ? $this->userid : $this->core()->user()->getUserProp($this->getLowerCaseName(), 'id'); }

    public function cash() { $this->core()->cash()->getCash($this->getuserid()); }

    protected $password = null;
    public function getPassword(): string { return $this->password = $this->password ? $this->password : $this->core()->user()->getUserProp($this->getLowerCaseName(), 'password'); }

    public function identification(): bool {
        $this->setreg($this->core()->user()->isRegistered($this->getLowerCaseName()));
        $this->checkauth();
        return $this->isreg() && $this->isauth();
    }

    public function checkauth(): void {
        $this->setauth($this->core()->user()->canAutoLogin($this->getLowerCaseName(), $this->getAddress()));
    }

    public function getCash(): NBXMoney {
        return $this->core()->cash()->getCash($this->getId());
    }

    public function login(bool $update = false): void {
        $this->setauth(true);
        $this->setreg(true);
        $this->setImmobile(false);
        $this->removeEffect(\pocketmine\entity\Effect::BLINDNESS);
        $this->core()->user()->registerPlayer($this);
        $nameTag = $this->formatNameTag();
        $this->setDisplayName($nameTag);
        if ($update) $this->core()->user()->setUserProp($this->getLowerCaseName(), ['address' => $this->getAddress(), 'modify' => true, 'lastlogin' => time()]);
		if (\Richen\Engine\Auction\Auction::getInstance()->hasInvalidatedItems($this)){
			$this->sendMessage("§a➛ §fС аукциона были сняты §6твои предметы§f. Вернуть их: §a/auc back");
			$this->sendMessage("§a➛ §fНе забудь освободить §cместо в инвентаре!");
		}
    }

    public function logout(): void {
        $this->setauth(false);
        $this->setreg(false);
        $this->setImmobile(true);
		$this->setScale(1.0);
        $this->getPlayerStats()->addProp('online', time() - $this->getLastLogin());
        $this->core()->stat()->despawnTopsForPlayer($this);
    }

    protected ?int $lastlogin = null;
    public function getLastLogin(): int {
        return $this->lastlogin = $this->lastlogin ? $this->lastlogin : $this->core()->user()->getUserProp($this->getLowerCaseName(), 'lastlogin');
    }

    public function title(string $title, string $subtitle, int $countdown = 0, $fadeIn = 20, $stay = 20, $fadeOut = 20) {
        $this->core()->getServer()->getScheduler()->scheduleDelayedTask(new class($this, $title, $subtitle, $fadeIn, $stay, $fadeOut) extends \pocketmine\scheduler\Task {
            private NBXPlayer $player;
            private string $title, $subtitle;
            private int $fadeIn, $stay, $fadeOut;
            public function __construct(NBXPlayer $player, $title, $subtitle, $fadeIn, $stay, $fadeOut) { $this->player = $player; $this->title = $title; $this->subtitle = $subtitle; $this->fadeIn = $fadeIn; $this->stay = $stay; $this->fadeOut = $fadeOut; }
            public function onRun($currentTick) : void { $this->player->sendTitle($this->title, $this->subtitle, $this->fadeIn, $this->stay, $this->fadeOut); }
        }, $countdown);
    }

    protected $group = null;
    public function getGroupName(): string { return $this->group = $this->group ? $this->group : $this->core()->user()->getUserProp($this->getLowerCaseName(), 'group'); }
    public function getGroup(): array { return $this->core()->conf('groups')->config()->getAll()[$this->getGroupName()] ?? $this->core()->conf('groups')->config()->getAll()['guest']; }
    private $prefix = null;
    public function getPrefix(): string { return $this->prefix = $this->prefix ? $this->core()->user()->getUserProp($this->getLowerCaseName(), 'prefix') ?? $this->getDefaultPrefix() : $this->getDefaultPrefix(); }
    public function getDefaultPrefix(): string { return $this->core()->conf('groups')->config()->getAll()[$this->getGroupName()]['prefix'] ?? 'Player'; }
    public function formatMessage($message): string {
        $group = $this->getGroupName();
        $groups = $this->core()->conf('groups')->config()->getAll();
        $format = $groups[$group]['chat'] ?? $groups['guest']['chat'];
        $prefix = $this->getPrefix();
        $name = $this->getName();
        $clan = $this->getClan();
        $clanTag = '';
        if ($clan) $clanTag = '§8[' . $clan->getNameTag() . '§8] ';
        $data = [
            '{clan}' => $clanTag,
            '{username}' => $name,
            '{prefix}' => $prefix,
            '{message}' => $message
        ];
        return str_replace(array_keys($data), array_values($data), $format);
    }
    public function formatNameTag(): string {
        $group = $this->getGroupName();
        $groups = $this->core()->conf('groups')->config()->getAll();
        $format = $groups[$group]['tag'] ?? $groups['guest']['tag'];
        $prefix = $this->getPrefix();
        $name = $this->getName();
        $clan = $this->getClan();
        $clanTag = '';
        if ($clan) $clanTag = '§8[' . $clan->getNameTag() . '§8] ';
        $data = [
            '{clan}' => $clanTag,
            '{username}' => $name,
            '{prefix}' => $prefix
        ];

        return str_replace(array_keys($data), array_values($data), $format);
    }

    public function core(): \Richen\NubixCore { return \Richen\NubixCore::$core; }
    protected ?PlayerStats $stats = null;
    public function getPlayerStats(): PlayerStats {
        return $this->stats = $this->stats ? $this->stats : new PlayerStats($this->getuserid());
    }

    protected $sit = null;

    public function isSit(): bool { return $this->sit !== null; }
    public function unsetSit(): void { $this->sit = null; }
    public function getSitId() { return $this->sit; }

    public function sitHere() {
        $packets2batch = [];

		$pk = new \pocketmine\network\mcpe\protocol\AddEntityPacket();
		$pk->eid = \pocketmine\entity\Entity::$entityCount++;
		$pk->type = 84;
		$pk->x = $this->getFloorX() + 0.5;
		$pk->y = $this->getFloorY() - ($this->getScale() >= 1.0 ? 0.75 : 0.4);
		$pk->z = $this->getFloorZ() + 0.5;
		$pk->metadata = [
			\pocketmine\entity\Entity::DATA_FLAGS => [\pocketmine\entity\Entity::DATA_TYPE_LONG, (1 << \pocketmine\entity\Entity::DATA_FLAG_IMMOBILE) | (1 << \pocketmine\entity\Entity::DATA_FLAG_INVISIBLE)]
		];
		$pk->speedX = $pk->speedY = $pk->speedZ = $pk->yaw = $pk->pitch = 0;

		$packets2batch[] = $pk;

		$this->setDataFlag(\pocketmine\entity\Entity::DATA_FLAGS, \pocketmine\entity\Entity::DATA_FLAG_RIDING, true);
		$this->setDataProperty(\pocketmine\entity\Entity::DATA_RIDER_SEAT_POSITION, \pocketmine\entity\Entity::DATA_TYPE_VECTOR3F, [0.0, 1.5, 0.0]);

		$pk2 = new \pocketmine\network\mcpe\protocol\SetEntityLinkPacket();
		$pk2->from = $pk->eid;
		$pk2->to = $this->getId();
		$pk2->type = 1;

		$packets2batch[] = $pk2;

		$this->core()->serv()->batchPackets($this->core()->serv()->getOnlinePlayers(), $packets2batch);

		$this->sit = $pk->eid;

        $this->core()->serv()->getScheduler()->scheduleDelayedTask(new class($this) extends \pocketmine\scheduler\Task {
            private $player; public function __construct(NBXPlayer $player) { $this->player = $player; }
            public function onRun($currentTick) : void { $this->player->addTitle('', '§eВы присели', 10, 20, 10); }
        }, 20);
    }

    public function standUp() {
		if (!$this->sit) return;

		$packets2batch = [];

		$pk = new \pocketmine\network\mcpe\protocol\RemoveEntityPacket();
		$pk->eid = $this->sit;

		$packets2batch[] = $pk;

		$pk2 = new \pocketmine\network\mcpe\protocol\SetEntityLinkPacket();
		$pk2->from = $pk->eid;
		$pk2->to = $this->getId();
		$pk2->type = 0;

		$packets2batch[] = $pk2;

		$this->setDataFlag(\pocketmine\entity\Entity::DATA_FLAGS, \pocketmine\entity\Entity::DATA_FLAG_RIDING, false);

		$this->core()->serv()->batchPackets($this->core()->serv()->getOnlinePlayers(), $packets2batch);

		$this->unsetSit();
	}

	public const PC = 1, MOBILE = 2, GAMEPAD = 3;
    public const PLATFORMS = [self::PC => 'PC', self::MOBILE => 'Mobile', self::GAMEPAD => 'Gamepad'];
    private const DAMAGE_MULTIPLIERS = [self::PC => 0.80, self::MOBILE => 1.0, self::GAMEPAD => 1.0];
    public $platform = null;
    public function getPlatform(): int { return $this->platform ? $this->platform : self::MOBILE; }
    public function getPlatformAsString(): ?string { return self::PLATFORMS[$this->getPlatform()]; }
    public function getDamageMultiplier(): float { return self::DAMAGE_MULTIPLIERS[$this->getPlatform()]; }
    public function setPlatform(int $platform): void { $this->platform = $platform; }

    public int $fight = 0;
    public function inFight(): bool { return $this->fight > 0; }
    public function getFight(): int { return $this->fight; }
    public function setFight(int $val) { $this->fight = $val; }
    public array $tiles = [];
    public function addTile(\pocketmine\tile\Tile $tile) { $this->tiles[] = $tile; }
    public function closeTile() {
        foreach ($this->tiles as $tile) {
            if ($tile instanceof \pocketmine\tile\Tile && $tile instanceof \pocketmine\tile\Chest) {
                $tile->getInventory()->close($this);
            }
        }
        $this->tiles = [];
    }
    public bool $iswarp = false;
    public function isWarp(): bool { return $this->iswarp; }
    public function setWarp(bool $val) { $this->iswarp = $val; }
}