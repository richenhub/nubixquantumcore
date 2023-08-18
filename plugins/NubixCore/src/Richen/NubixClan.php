<?php 

namespace Richen;

use pocketmine\level\Position;
use Richen\Custom\NBXClan;
use Richen\Custom\NBXPlayer;

class NubixClan extends Engine\Manager {
    public function __construct() {
        $query = "INSERT INTO clans (clan, owner, lvl, exp, color, home) VALUES ";
        for ($i = 1; $i <= 10; $i++) {
            $clan = "clan" . rand(1, 1000);
            $owner = "owner" . rand(1, 1000);
            $lvl = rand(1, 10);
            $exp = rand(0, 10000);
            $color = rand(0, 6);
            $home = "Home" . rand(1, 1000);
            $query .= "('$clan', '$owner', $lvl, $exp, $color, '$home')";
            if ($i != 10) {
                $query .= ",";
            }
        }
        //$this->core()->data()->conn()->exec($query);
    }
    protected array $players, $clans;
    const OWNER = 0;
    const MEMBER = 1;
    const STAFF = 2;
    const RANKS = [
        0 => 'владелец',
        1 => 'участник',
        2 => 'модератор'
    ];

    public function setClanProp(string $clan, array $props): bool {
        $props['modify'] = time();
        return (bool) $this->data()->update('clans', $props, [['k' => 'clan', 'v' => mb_strtolower($clan)]]);
    }

    public function getClanProp(string $username, string $prop) {
        $res = $this->data()->get('clans', [['k' => 'clan', 'v' => mb_strtolower($username)]]);
        return count($res) ? $res[0][$prop] : null;
    }

    public function getClanByName($clan): ?NBXClan {
        $clan = mb_strtolower($clan);
        return $this->clans[$clan] ?? $this->clans[$clan] = $this->prepareClan($this->core()->data()->get('clans', [['k' => 'clan', 'v' => $clan]])[0] ?? []);
    }

    public function getClanById(int $id): ?NBXClan {
        $res = $this->core()->data()->get('clans', [['k' => 'id', 'v' => $id]])[0] ?? [];
        $clan = $this->prepareClan($res);
        if ($clan instanceof NBXClan) {
            $this->clans[$clan->getName()] = $clan;
            return $clan;
        }
        return null;
    }

    public function getAllClans(): array {
        return $this->data()->get('clans', [], [['key' => 'lvl', 'isd' => 1], ['key' => 'clan', 'isd' => 0]]) ?? [];
    }

    public function setHome(NBXClan $clan, Position $home) {
        $this->data()->update('clans', ['home' => $home->__toString()], [['k' => 'id', 'v' => $clan->getId()]]);
        $clan->setHome($home->__toString());
    }

    public function prepareClan(array $data): ?NBXClan {
        if (count($data) === 9) {
            return new NBXClan($data['id'], $data['clan'], $data['owner'], $data['lvl'], $data['exp'], $data['color'], $data['created'], $data['home'] ?? '', $data['modify']);
        }
        return null;
    }

    public function clanExists(string $clan): bool {
        return $this->data()->count('clans', [['k' => 'clan', 'v' => mb_strtolower($clan)]]) > 0;
    }

    public function getUserClan(string $nick): ?NBXClan {
        $nick = mb_strtolower($nick);
        if (isset($this->players[$nick])) {
            $clan = $this->getClanByName($this->players[$nick]);
            if ($clan instanceof NBXClan) {
                $this->players[$nick] = $this->clans[$clan->getName()]->getName();
                return $clan;
            }
        }
        $res = $this->data()->get('clanmembers', [['k' => 'username', 'v' => mb_strtolower($nick)]]);
        $clan = count($res) ? $this->getClanById($res[0]['clan_id']) : null;
        if ($clan instanceof NBXClan) {
            $this->players[$nick] = ($this->clans[$clan->getName()] = $clan)->getName();
            return $clan;
        }
        return null;
    }

    public function getByRank(int $id, int $rank) {
        return $this->data()->get('clanmembers', [['k' => 'clan_id', 'v' => $id], ['k' => 'rank', 'v' => $rank]]);
    }

    public function getStaffs(int $id): array {
        return array_reduce($this->getByRank($id, self::STAFF), function($staffs, $data) { $staffs[$data['username']] = $data; return $staffs; }, []);
    }

    public function getMembers(int $id): array {
        return array_reduce($this->getByRank($id, self::MEMBER), function($members, $data) { $members[$data['username']] = $data; return $members; }, []);
    }

    public function getKills(int $id): int {
        return $this->data()->get('clanstats', [['k' => 'clan_id', 'v' => $id]])[0]['kills'] ?? 0;
    }

    public function addOnline(int $id, int $time) {
        $this->data()->update('clanstats', ['online' => $this->getOnline($id) + $time], [['k' => 'clan_id', 'v' => $id]]);
        $clan = $this->getClanById($id);
        if ($clan) $this->clans[$clan->getName()]->addOnline($time);
    }

    public function getOnline(int $id): int {
        return $this->data()->get('clanstats', [['k' => 'clan_id', 'v' => $id]])[0]['online'] ?? 0;
    }

    public function getDeath(int $id): int {
        return $this->data()->get('clanstats', [['k' => 'clan_id', 'v' => $id]])[0]['death'] ?? 0;
    }

    public function createClan(string $clan, string $username): int {
        $clan = mb_strtolower($clan);
        $username = mb_strtolower($username);
        $id = $this->data()->add('clans', ['clan' => $clan, 'owner' => $username]);
        $this->data()->add('clanmembers', ['clan_id' => $id, 'username' => $username, 'rank' => self::OWNER]);
        $this->data()->add('clanstats', ['clan_id' => $id]);
        return $id;
    }

    public function deleteClan(int $id) {
        $clan = $this->getClanById($id);
        $this->data()->delete('clans', [['k' => 'id', 'v' => $id]]);
        $this->data()->delete('clanmembers', [['k' => 'clan_id', 'v' => $id]]);
        $this->data()->delete('clanstats', [['k' => 'clan_id', 'v' => $id]]);
        unset($this->clans[$clan->getName()]);
    }

    public function kick(NBXClan $clan, string $username): bool {
        if ($clan->isMember($username) && !$clan->isOwner($username)) {
            try {
                $this->data()->delete('clanmembers', [['k' => 'username', 'v' => $username], ['k' => 'clan_id', 'v' => $clan->getId()]]);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    public function promote(NBXClan $clan, string $username): bool {
        $this->clans[$clan->getName()]->delMember($username);
        $this->clans[$clan->getName()]->addStaff($username);
        $this->data()->update('clanmembers', ['rank' => self::STAFF], [['k' => 'username', 'v' => mb_strtolower($username)], ['k' => 'clan_id', 'v' => $clan->getId()]]);
        return true;
    }

    public function demote(NBXClan $clan, string $username): bool {
        $this->clans[$clan->getName()]->delStaff($username);
        $this->clans[$clan->getName()]->addMember($username);
        $this->data()->update('clanmembers', ['rank' => self::MEMBER], [['k' => 'username', 'v' => mb_strtolower($username)], ['k' => 'clan_id', 'v' => $clan->getId()]]);
        return true;
    }

    public function needExp(int $lvl) { return $lvl ** 2 * 10; }

    public function getExpStats(int $lvl, int $exp) { return '§e' . $exp . '§7/§6' . $this->needExp($lvl); }

    public function addMember(NBXClan $clan, string $username) {
        $this->data()->add('clanmembers', ['username' => mb_strtolower($username), 'clan_id' => $clan->getId(), 'rank' => self::MEMBER]);
    }

    public function removeMember(NBXClan $clan, string $username) {
        $this->data()->delete('clanmembers', [['k' => 'username', 'v' => mb_strtolower($username)], ['k' => 'clan_id', 'v' => $clan->getId()]]);
    }

    public function getOnlinePlayers(NBXClan $clan): array {
        $players = [];
        foreach ($this->core()->getServer()->getOnlinePlayers() as $player) {
            if ($player instanceof NBXPlayer) {
                if ($player->getClan()->getId() === $clan->getId()) {
                    $players[] = $player;
                }
            }
        }
        return $players;
    }

    public function broadcastMessage(NBXClan $clan, string $message, ?string $senderName = null) {
        if ($senderName) {
            $prefix = '§7Участник §f♟';
            if ($clan->isStuff($senderName ?? '')) $prefix = '§2Модер §a☆';
            if ($clan->isOwner($senderName ?? '')) $prefix = '§6Лидер §e✩';
            $prefix = '§f' . ($prefix ?? '') . ' §7' . ($senderName ?? '') . ' ';
        }
        foreach ($this->getOnlinePlayers($clan) as $player) {
            $player->sendMessage('§8[§6Клан§7-§fЧат§8] §8[' . $clan->getNameTag() . '§8] ' . ($prefix ?? '') . '§6> §f' . $message);
        }
        $this->serv()->getLogger()->info('§8[§6Клан§7-§fЧат§8] §8[' . $clan->getNameTag() . '§8] ' . ($prefix ?? '') . '§6> §f' . $message);
        unset($this->clans[$clan->getNameTag()]);
    }
}