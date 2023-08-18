<?php 

namespace Richen\Custom;

use NBX\Utils\Chat;
use NBX\Utils\Values;
use pocketmine\utils\TextFormat as C;
use Richen\Engine\Consts;
use Richen\Engine\Utils;

class NBXClan {
    private int $id;
    private string $clan;
    private string $owner;
    private string $nameTag;
    private int $lvl;
    private int $exp;
    private int $kills;
    private int $death;
    private int $color;
    private int $created;
    private string $home;
    private array $colors;
    private array $members;
    private array $staffs;
    private int $datemodify;
    private int $online;
    private function core(): \Richen\NubixCore { return \Richen\NubixCore::core(); }
    public function __construct(int $id, string $clan, string $owner, int $lvl, int $exp, int $color, int $created, string $home, int $datemodify) {
        $this->id = $id;
        $this->clan = mb_strtolower($clan);
        $this->owner = mb_strtolower($owner);
        $this->nameTag = mb_strtoupper($clan);
        $this->lvl = $lvl;
        $this->exp = $exp;
        $this->kills = $this->core()->clan()->getKills($id);
        $this->death = $this->core()->clan()->getDeath($id);
        $this->color = $color;
        $this->created = $created;
        $this->home = $home;
        $this->colors = Consts::getClanColors();
        $this->members = $this->core()->clan()->getMembers($id);
        $this->staffs = $this->core()->clan()->getStaffs($id);
        $this->datemodify = $datemodify;
        $this->online = $this->core()->clan()->getOnline($id);
    }
    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->clan; }
    public function getOwner(): string { return $this->owner; }
    public function getHome(): ?\pocketmine\level\Position { return Utils::strToPosition($this->home); }
    public function setHome(string $home) { $this->home = $home;}
    public function getNameTag(): string { return $this->color === 10 ? Utils::rainbowify($this->nameTag, mt_rand(0,9)) : $this->getColor() . $this->nameTag; }
    public function getColor(): string { return $this->colors[$this->color] ?? C::GRAY; }
    public function setColor($color) { $this->color = $color; }
    public function isOwner(string $nick): bool { return $this->owner === mb_strtolower($nick); }
    public function isMember(string $nick): bool { return isset($this->members[mb_strtolower($nick)]) || $this->isOwner($nick) || $this->isStuff($nick); }
    public function addMember(string $nick) { $this->members[] = mb_strtolower($nick); }
    public function delMember(string $nick) { unset($this->members[mb_strtolower($nick)]); $this->members = array_keys($this->members); }
    public function isStuff(string $nick): bool { return isset($this->staffs[mb_strtolower($nick)]) || $this->isOwner($nick); }
    public function addStaff(string $nick) { $this->staffs[] = mb_strtolower($nick); }
    public function delStaff(string $nick) { unset($this->staffs[mb_strtolower($nick)]); $this->staffs = array_keys($this->staffs); }
    public function getMembers(): array { return $this->members; }
    public function getStaffs(): array { return $this->staffs; }
    public function getCash(): NBXMoney { return $this->core()->cash()->getCash(($this->getId() + 2000000000) * -1); }
    public function getLVL(): int { return $this->lvl; }
    public function getExp(): int { return $this->exp; }
    public function getOnline(): int { return $this->online; }
    public function addOnline(int $time) { $this->online += $time; }
}