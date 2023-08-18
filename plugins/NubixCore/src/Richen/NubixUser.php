<?php

namespace Richen;

use pocketmine\IPlayer;
use pocketmine\Player;
use Richen\Custom\NBXPlayer;

class NubixUser extends Engine\Manager {
    public function __construct() {}
    public function setUserProp(string $username, array $props): bool {
        $props['datemodify'] = time();
        try { $this->data()->update('users', $props, [['k' => 'username', 'v' => mb_strtolower($username)]]); return true;
        } catch (\Exception $e) { return false; }
    }

    public function getUserProp(string $username, string $prop) {
        if ($prop === 'id' && mb_strtolower($username) === 'nubix') return 0;
        $res = $this->data()->get('users', [['k' => 'username', 'v' => mb_strtolower($username)]]);
        if (!isset($res[0][$prop])) return null;
        $resprop = $res[0][$prop];
        if ($prop === 'group' && !$this->core()->conf('groups')->config()->exists($resprop)) return 'guest';
        return $resprop;
    }

    public function getUserPropById(int $id, string $prop) {
        if ($prop === 'username' && $id === 0) return 'nubix';
        $res = $this->data()->get('users', [['k' => 'id', 'v' => $id]]);
        if (!isset($res[0][$prop])) return null;
        $resprop = $res[0][$prop];
        return $resprop;
    }

    public function isRegistered(string $username): bool { return $this->data()->count('users', [['k' => 'username', 'v' => mb_strtolower($username)]]) > 0; }
    public function canAutoLogin(string $username, string $address) { return $this->data()->count('users', [['k' => 'username', 'v' => mb_strtolower($username)], ['k' => 'address', 'v' => $address]]) > 0; }
    public function register(string $username, string $password, string $address): int { return $this->data()->add('users', ['username' => $username, 'password' => $password, 'address' => $address]); }
    public function getPermissions(Player $player) {
        if ($player instanceof NBXPlayer) {
            $groups = $this->core()->conf('groups')->config()->getAll();
            $group = $player->getGroupName();
            $otherperms = [];
            foreach ($groups as $groupname => $groupdata) if ($groupdata['index'] <= $groups[$group]['index']) $otherperms = array_merge($otherperms, $groupdata['perms']);
            $perms = array_merge($groups[$group]['perms'], [/* todo self player perms */], $otherperms);
            return array_unique($perms);
        }
        return [];
    }
    
	private array $ats = [];
    public function updatePermissions(IPlayer $player) {
        if (!$player instanceof NBXPlayer) return;
        $permissions = array_map(function ($permission) { $isNegative = mb_substr($permission, 0, 1) === '-'; if ($isNegative) $permission = mb_substr($permission, 1); $value = !$isNegative; return [$permission, $value]; }, $this->core()->user()->getPermissions($player));
        $at = $this->ats[$player->getUniqueId()->toString()];
        $at->clearPermissions();
        $at->setPermissions(array_reduce($permissions, function ($carry, $permission) { $carry[$permission[0]] = $permission[1]; return $carry; }, []));
    }
    public function registerPlayer(Player $player) { if (isset($this->ats[($uniqueId = $player->getUniqueId()->toString())])) $this->unregisterPlayer($player); $at = $player->addAttachment($this->core()); $this->ats[$uniqueId] = $at; $this->updatePermissions($player); }
    public function unregisterPlayer(Player $player) { $uniqueId = $player->getUniqueId()->toString(); if (isset($this->ats[$uniqueId])) $player->removeAttachment($this->ats[$uniqueId]); unset($this->ats[$uniqueId]); }

}