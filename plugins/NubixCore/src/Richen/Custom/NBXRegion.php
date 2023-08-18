<?php 

namespace Richen\Custom;

use pocketmine\level\Position;

interface IRegion {
    public function getId(): int;
    public function getName(): string;
    public function getOwner(): string;
    public function getLevelName(): string;
    public function getMinPosition(): Position;
    public function getMaxPosition(): Position;
    public function setPosition(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2): void;
    public function getPosition(): array;
    public function setOwner(string $owner): void;
    public function setName(string $name): void;
}

class NBXRegion implements IRegion {
    private int $id;
    private string $name;
    private string $owner;
    private string $level;
    private array $members;
    private Position $minPosition;
    private Position $maxPosition;
    private int $sell;
    private array $flags = [
        'pvp' => false,
        'use' => false,
        'chest' => false,
    ];

    private bool $hasChanged = false;

    public function __construct($id, $name, $owner, $x1, $y1, $z1, $x2, $y2, $z2, $level, $sell, $members, $flags) {
        $this->id = $id;
        $this->name = $name;
        $this->owner = $owner;
        $this->level = $level;
        $this->members = $members;
        $this->flags = $flags + $this->flags;
        $this->sell = $sell;
        $this->setPosition($x1, $y1, $z1, $x2, $y2, $z2);
    }

    public function isRegion(): bool {
        return $this->id > 0;
    }

    public function getMembers(): array {
        return $this->members;
    }

    public function save() {
        
    }
    
    public function setFlagStatus(string $flag, bool $status) {
        $this->flags[$flag] = $status;
    }

    public function getFlags(): array {
        return $this->flags;
    }

    public function getFlag($property): bool {
        return $this->flags[$property] ?? false;
    }

    public function isMember(string $username) {
        return in_array(mb_strtolower($username), $this->members) || mb_strtolower($username) === $this->owner;
    }

    public function hasChanged(): bool {
        return $this->hasChanged;
    }

    public function setChanged(): void {
        $this->hasChanged = true;
    }
    
    public function getId(): int {
        return $this->id;
    }
    
    public function getName(): string {
        return $this->name;
    }
    
    public function getOwner(): string {
        return $this->owner;
    }

    public function getLevelName(): string {
        return $this->level;
    }

    public function getLevel(): \pocketmine\level\Level {
        return \pocketmine\Server::getInstance()->getLevelByName($this->level);
    }

    public function getMinPosition(): Position {
        return $this->minPosition;
    }

    public function getMaxPosition(): Position {
        return $this->maxPosition;
    }

    public function getCenterCoords(): array {
        $pos = $this->getPosition();
        $min = $pos['min'];
        $max = $pos['max'];

        $x = ($min['x'] + $max['x']) / 2;
        $y = ($min['y'] + $max['y']) / 2;
        $z = ($min['z'] + $max['z']) / 2;
    
        return ['x' => (int)$x, 'y' => (int)$y, 'z' => (int)$z];
    }

    public function setPosition(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2): void {
        $this->minPosition = new Position(min($x1, $x2), min($y1, $y2), min($z1, $z2), $this->getLevel());
        $this->maxPosition = new Position(max($x1, $x2), max($y1, $y2), max($z1, $z2), $this->getLevel());
    }

    public function getPosition(): array {
        return [
            'min' => ['x' => $this->minPosition->getX(), 'y' => $this->minPosition->getY(), 'z' => $this->minPosition->getZ()],
            'max' => ['x' => $this->maxPosition->getX(), 'y' => $this->maxPosition->getY(), 'z' => $this->maxPosition->getZ()],
        ];
    }

    public function setOwner(string $owner): void {
        $this->owner = $owner;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function setLevelName(string $levelName): void {
        $this->level = $levelName;
    }

    public function removeMember(string $username): void {
        unset($this->members[array_search(mb_strtolower($username), $this->members)]);
    }

    public function addMember(string $username): void {
        $this->members[] = mb_strtolower($username);
    }

    public function updatePrice(int $price): void {
        $this->sell = $price;
    }

    public function isSell(): bool {
        return $this->sell > 0;
    }

    public function getPrice(): int {
        return $this->sell;
    }
}