<?php 

namespace Richen;

use pocketmine\level\Level;
use Richen\Custom\NBXRegion;

class NubixRgns extends Engine\Manager {
    private array $regions = [];

    protected array $pos1 = [], $pos2 = [];

    protected array $flags = [
        'pvp' => 'ПВП в привате',
        'use' => 'Взаимодействие в привате',
        'chest' => 'Открывать сундуки в привате',
        'cmd' => 'Использовать команды',
        'damage' => 'Получение урона в привате',
        'build' => 'Строительство в привате'
    ];

    public function getAllFlags(): array {
        return $this->flags;
    }

    public function getFlagStatus(NBXRegion $region, $flag) {
        if (isset($flags[$flag])) {
            $region->getFlag($flag);
        }
        return false;
    }

    public function __construct() {
        $this->loadRegions();
    }

    public function setPos1(string $nick, int $x, int $y, int $z, Level $level) {
        $this->pos1[$nick] = new \pocketmine\level\Position($x, $y, $z, $level);
    }

    public function setPos2(string $nick, int $x, int $y, int $z, Level $level) {
        $this->pos2[$nick] = new \pocketmine\level\Position($x, $y, $z, $level);
    }

    public function countBlocks($x1, $y1, $z1, $x2, $y2, $z2) {
        $minX = min($x1, $x2);
        $minY = min($y1, $y2);
        $minZ = min($z1, $z2);
    
        $maxX = max($x1, $x2);
        $maxY = max($y1, $y2);
        $maxZ = max($z1, $z2);
    
        return abs(($maxX - $minX + 1) * ($maxY - $minY + 1) * ($maxZ - $minZ + 1));
    }

    public function isValidName($str): bool {
        return preg_match("/^[0-9a-z]+$/i", $str);
    }

    public function getValidSelect($x1, $y1, $z1, $x2, $y2, $z2): array {
        $minX = min($x1, $x2);
        $minY = min($y1, $y2);
        $minZ = min($z1, $z2);
    
        $maxX = max($x1, $x2);
        $maxY = max($y1, $y2);
        $maxZ = max($z1, $z2);

        $conditions = [
            ['k' => 'x2', 'v' => $minX, 'o' => 7],
            ['k' => 'x1', 'v' => $maxX, 'o' => 8],
            ['k' => 'y2', 'v' => $minY, 'o' => 7],
            ['k' => 'y1', 'v' => $maxY, 'o' => 8],
            ['k' => 'z2', 'v' => $minZ, 'o' => 7],
            ['k' => 'z1', 'v' => $maxZ, 'o' => 8],
        ];
        return $conditions;
    }

    public function isValidPos(string $nick): string {
        if (isset($this->pos1[$nick]) && isset($this->pos2[$nick])) {
            if ($this->pos1[$nick]->getLevel()->getFolderName() === $this->pos2[$nick]->getLevel()->getFolderName()) {
                $x1 = $this->pos1[$nick]->getX();
                $y1 = $this->pos1[$nick]->getY();
                $z1 = $this->pos1[$nick]->getZ();
                $x2 = $this->pos2[$nick]->getX();
                $y2 = $this->pos2[$nick]->getY();
                $z2 = $this->pos2[$nick]->getZ();
                
                $count = $this->countBlocks($x1, $y1, $z1, $x2, $y2, $z2);
                $maxBlocks = $this->getMaxBlocks($nick);
                $maxRegions = $this->getMaxRegions($nick);

                if ($count > $maxBlocks) {
                    unset($this->pos1[$nick]);
                    unset($this->pos2[$nick]);
                    return  '[!] Вы не можете заприватить больше ' . $maxBlocks . ' блоков' . PHP_EOL . 
                            '[!] У привилегий выше вашей количество и размеры приватов больше!' . PHP_EOL .
                            '[!] Точки были сброшены, установите снова: /rg pos1 и /rg pos2';
                } else {
                    $rg_count = $this->data()->count('region', [['k' => 'owner', 'v' => $nick]]);
			        if (($rg_count ?? 0) > $maxRegions) {
                        return  '[!] Вы не можете создать больше ' . $maxRegions . ' приватов' . PHP_EOL . 
                                '[!] У привилегий выше вашей количество и размеры приватов больше!' . PHP_EOL .
                                '[!] Точка не была создана, удалите лишние приваты или улучшите привилегию';
                    } else {
                        $level = mb_strtolower($this->pos1[$nick]->getlevel()->getFolderName());
                        $result = $this->data()->get('region', [$this->getValidSelect($x1, $y1, $z1, $x2, $y2, $z2), ['k' => 'level', 'v' => $level]]);
                        foreach ($result as $row) {
                            if ($row['owner'] !== $nick) {
                                unset($this->pos1[$nick]);
                                unset($this->pos2[$nick]);
                                return  '[!] Выбранная территория пересекает чужую территорию игрока ' . $row['owner'] . PHP_EOL . 
                                        '[!] Точки были сброшены, установите снова: /rg pos1 и /rg pos2';
                            }
                        }
                        return $count;
                    }
                }
            } else {
                return '[!] Точки должны находиться в одном мире';
            }
        } else {
            return '[!] Вы должны установить две точки /rg pos1 и /rg pos2';
        }
    }

    public function getMaxBlocks(string $nick) {
        return 1000;
    }

    public function getMaxRegions(string $nick) {
        return 2;
    }

    public function setFlagStatus(NBXRegion $rg, string $flag, bool $status) {
        $result = $this->data()->get('flags', [['k' => 'region_id', 'v' => $rg->getId()], ['k' => 'flag', 'v' => $flag]]);
        if (isset($result['region_id'])) {
            $this->data()->update('flags', [$flag => $status], [['k' => 'region_id', 'v' => $rg->getId()]]);
        } else {
            $this->data()->add('flags', ['region_id' => $rg->getId(), 'flag' => $flag, 'status' => $status]);
        }
    }

    public function getRegion(string $region): NBXRegion {
        $region = mb_strtolower($region);
        if (!isset($this->regions[$region])) {
            $result = $this->data()->get('region', [['k' => 'region', 'v' => $region]]);
            if (isset($result['region'])) {
                $rg = $this->getRegionByData($result);
                $this->regions[$region] = $rg;
            } else {
                return $this->getEmptyRegion();
            }
        }
        return $this->regions[$region];
    }

    public function getEmptyRegion(): NBXRegion {
        return new NBXRegion(0, '', '', 0, 0, 0, 0, 0, 0, 'world', 0, [], []);
    }

    public function getRegionByData($data): NBXRegion {
        $owner = $data['owner'];
        $x1 = $data['x1'];
        $y1 = $data['y1'];
        $z1 = $data['z1'];
        $x2 = $data['x2'];
        $y2 = $data['y2'];
        $z2 = $data['z2'];
        $level = $data['level'];
        $members = $this->getMembers($data['id']);
        $flags = $this->getFlags($data['id']);
        return new NBXRegion($data['id'], $data['region'], $owner, $x1, $y1, $z1, $x2, $y2, $z2, $level, $data['sell'], $members, $flags);
    }

    public function getRegionByPos(int $x, int $y, int $z, string $world): NBXRegion {      
        $result = $this->data()->get('region',
            [
                ['k' => 'x1', 'v' => $x, 'o' => 8],
                ['k' => 'x2', 'v' => $x, 'o' => 8],
                ['k' => 'y1', 'v' => $y, 'o' => 8],
                ['k' => 'y2', 'v' => $y, 'o' => 8],
                ['k' => 'z1', 'v' => $z, 'o' => 8],
                ['k' => 'x2', 'v' => $z, 'o' => 8],
                ['k' => 'level', 'v' => mb_strtolower($world)]
            ]
        );
        if (isset($result['region'])) {
            return $this->getRegionByData($result);
        } else {
            return $this->getEmptyRegion();
        }
    }

    public function getFlags(int $id): array {
        $result = $this->data()->get('flags', [['k' => 'region_id', 'v' => $id]]);
        $flags = [];
        foreach ($result as $row) {
            $flags[$row['flag']] = $row['status'];
        }
        return $flags;
    }

    public function createRegion(string $region, string $owner): NBXRegion {
        $level = mb_strtolower($this->pos1[mb_strtolower($owner)]->getLevel()->getFolderName());
        $x1 = $this->pos1[mb_strtolower($owner)]->getX();
        $y1 = $this->pos1[mb_strtolower($owner)]->getY();
        $z1 = $this->pos1[mb_strtolower($owner)]->getZ();
        $x2 = $this->pos2[mb_strtolower($owner)]->getX();
        $y2 = $this->pos2[mb_strtolower($owner)]->getY();
        $z2 = $this->pos2[mb_strtolower($owner)]->getZ();

        $minX = min($x1, $x2); $maxX = max($x1, $x2);
        $minY = min($y1, $y2); $maxY = max($y1, $y2);
        $minZ = min($z1, $z2); $maxZ = max($z1, $z2);

        $id = $this->data()->add('region', [
            'region' => mb_strtolower($region),
            'owner' => mb_strtolower($owner),
            'x1' => $minX, 'x2' => $maxX,
            'y1' => $minY, 'y2' => $maxY,
            'z1' => $minZ, 'z2' => $maxZ,
            'level' => $level
        ]);

        if (is_numeric($id) && $id > 0) {
            unset($this->regions[$region]);
        }

        return $this->getRegion($region);
    }
    
    public function saveRegion($name) {

    }

    public function deleteRegion(NBXRegion $region): void {
        $this->data()->delete('region', [['k' => 'id', 'v' => $region->getId()]]);
        $this->data()->delete('member', [['k' => 'region_id', 'v' => $region->getId()]]);
        $this->data()->delete('flags', [['k' => 'region_id', 'v' => $region->getId()]]);
        unset($this->regions[$region->getName()]);
    }

    public function getUserRegions($nick): array {
        $result = $this->data()->get('region', [['k' => 'owner', 'v' => mb_strtolower($nick)]]);
        $regions = [];
        foreach ($result as $data) {
            $regions[] = $this->getRegionByData($data);
        }
        return $regions;
    }

    public function getRegions(): array {
        return $this->regions;
    }

    public function getMembers(int $regionId) {
        $result = $this->data()->get('member', [['k' => 'region_id', 'v' => $regionId]]);
        $members = [];
        foreach ($result as $row) {
            $members[] = $row['username'];
        }
        return $members;
    }

    public function removeMember($region, string $nick) {
        $this->regions[$region->getName()]->removeMember($nick);
        $this->data()->delete('member', [['k' => 'region_id', 'v' => $region->getId()], ['k' => 'username', 'v' => mb_strtolower($nick)]]);
        return true;
    }

    public function addMember(int $regionId, string $nick) {
        if (is_numeric($this->data()->add('member', ['username' => mb_strtolower($nick), 'region_id' => $regionId]))) {
            return true;
        }
        return false;
    }

    public function updatePrice(int $regionId, int $price) {
        try {
            $this->data()->update('region', ['sell' => $price], [['k' => 'id', 'v' => $regionId]]);
            return true;
        } catch (\Exception $e) {
            return $this->data()->lastError();
        }
    }

    public function regionHere(int $x, int $y, int $z, string $world) {
        $count = $this->data()->count('region', [
            ['k' => 'x1', 'v' => $x, 'o' => 8],
            ['k' => 'x2', 'v' => $x, 'o' => 8],
            ['k' => 'y1', 'v' => $y, 'o' => 8],
            ['k' => 'y2', 'v' => $y, 'o' => 8],
            ['k' => 'z1', 'v' => $z, 'o' => 8],
            ['k' => 'x2', 'v' => $z, 'o' => 8],
            ['k' => 'level', 'v' => mb_strtolower($world)]
        ]);
		return $count > 0;
	}

    private function loadRegions() : void {
        $result = $this->data()->get('region');
        foreach ($result as $row) {
            $this->regions[$row['region']] = $this->getRegionByData($row);
        }
    }
}