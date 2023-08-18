<?php 

namespace Richen;

class NubixBans extends \Richen\Engine\Manager {
    CONST BAN = 1, MUTE = 2, KICK = 3, WARN = 4;
    public $types = ['unknown', 'ban', 'mute', 'kick', 'warn'];
    public array $ban = [], $mute = [];
    public function __construct() { self::$instance = $this; }
    public static NubixBans $instance; public static function getInstance() { return self::$instance; }

    public function hasType($type): bool { switch ($type) { case self::BAN: case self::MUTE: case self::KICK: case self::WARN: return true; } return false; }
    public function hasBan(string $username): bool { return ($this->ban[mb_strtolower($username)]['ban'] ?? 0) - time() > 0; }
    public function hasMute(string $username): bool { return ($this->mute[mb_strtolower($username)]['mute'] ?? 0) - time() > 0; }
    public function hasBanByType(string $username, int $type): bool { if (in_array($type, self::$types)) { return ($this->ban[mb_strtolower($username)]['ban'] ?? 0) - time() > 0; } return false; }
    public function getBanList(int $type) { return $this->data()->get('bansystem', [['k' => $this->types[$type], 'v' => time(), 'o' => 3], ['k' => 'status', 'v' => 1]]); }
    public function initBanList() {
        foreach (['ban', 'mute'] as $field) {
            $query = $this->data()->get('bansystem', ['k' => $field, 'v' => time(), 'o' => 3]);
            foreach ($query as $row) { $this->$field[$row['username']] = $row; }
        }
    }
    public function getBanInfo(string $username) {
        $infoBan = $this->data()->get('bansystem', [
            ['k' => 'username', 'v' => mb_strtolower($username)], 
            ['k' => 'ban', 'v' => time(), 'o' => 3],
            ['k' => 'status', 'v' => 1],
        ]);

        $infoMute = $this->data()->get('bansystem', [
            ['k' => 'username', 'v' => mb_strtolower($username)], 
            ['k' => 'mute', 'v' => time(), 'o' => 3],
            ['k' => 'status', 'v' => 1],
        ]);

        
        $message = '§3[§6NXBAN§3] §fИнформация о статусе игрока: §e' . $username;

        if (!count($infoBan) && !count($infoMute)) {
            $message = '§3[§6NXBAN§3] §fУ игрока нет ограничений';
        } else {
            if (count($infoMute)) {
                foreach ($infoBan as $key => $row) {
                    if ($row['mute'] > 0) {
                        $mute = $row;
                    }
                }
                if (isset($mute)) {
                    $message .= PHP_EOL . 'Игрок в муте';
                }
            }
            
            if (count($infoBan)) {
                foreach ($infoBan as $key => $row) {
                    if ($row['ban'] > 0) {
                        $ban = $row;
                    }
                }
                if (isset($ban)) {
                    $message .= PHP_EOL . 'Игрок в бане';
                }
            }
        }

        return $message;
    }
    public function getCounts(string $username, int $type) {
        $field = $this->types[$type];
        return $this->data()->count('bansystem', [['k' => 'username', 'v' => mb_strtolower($username)], ['k' => $field, 'v' => 0, 'o' => 3]]);
    }
    public function checkBan(string $username, int $type): array {
        $field = $this->types[$type] ?? 0;
        if ($field) {
            $result = $this->data()->get('bansystem', [['k' => 'username', 'v' => mb_strtolower($username)], ['k' => $field, 'v' => 0, 'o' => 3], ['k' => 'status', 'v' => 1]]);
            if (isset($result[0]['id'])) {
                return $result[0];
            }
        }
        return [];
    }
    public function delBan(int $type, string $sendername, string $username, string $reason) {
        $types = $this->types;
        if ($this->checkBan($username, $type)) {
            try {
                $this->data()->update('bansystem', [
                    'status' => 0,
                    'comment' => 'разблокирован игроком: ' . $sendername . ' в ' . date('d/m/Y-H:i', time()) . ', комментарий: ' . $reason
                ], [
                    ['k' => 'username', 'v' => $username],
                    ['k' => $types[$type], 'v' => 0, 'o' => 3]
                ]);
            } catch (\Exception $e) {
                return 'Ошибка при разблокировке игрока';
            }
            $field = $types[$type];
            unset($this->$field[$username]);
            return 1;
        } else {
            return 'Данный игрок '. $username .' не заблокирован';
        }
    }

    public function addBan(int $type, string $sendername, string $username, string $reason, int $time, string $address = '0.0.0.0', string $cid = '0'): int {
        $types = $this->types;
        $res = $this->data()->add('bansystem', [
            'username' => $username,
            $types[$type] => $time,
            'sender' => $sendername,
            'reason' => $reason,
            'address' => $address,
            'updated' => time(),
            'cid' => $cid
        ]);
                
        $field = $types[$type];
        $this->$field[$username] = $this->checkBan($username, $type);
        return $res;
    }

    public function setBan(int $type, string $sendername, string $username, string $reason, int $time, string $address = '0.0.0.0', string $cid = '0'): string {
        $sendername = mb_strtolower($sendername);
        $username = mb_strtolower($username);

        if ($time === 0) $time = time();
        $diff = $time - time();

        switch ($type) {
            case self::BAN:
                if ($time < time()) { return '§4[!] §cОшибка: время окончания блокировки не должно быть меньше текущего'; }
                if ($diff > 7776000) {
                    return '§4[!] §cОшибка: максимальное время бана - §690 дней';
                }
                $baninfo = $this->checkBan($username, $type);
                if (!empty($baninfo)) {
                    return '§4[!] §cИгрок §6' . $username . ' §cуже заблокирован';
                }
                return $this->addBan($type, $sendername, $username, $reason, $time, $address, $cid);

            case self::MUTE:
                if ($time < time()) { return '§4[!] §cОшибка: время окончания блокировки не должно быть меньше текущего'; }
                if ($diff > 3600) {
                    return '§4[!] §cОшибка: максимальное время мута - §61 час';
                }
                $baninfo = $this->checkBan($username, $type);
                if (!empty($baninfo)) {
                    return '§4[!] §cИгрок §6' . $username . ' §cуже в муте';
                }
                return $this->addBan($type, $sendername, $username, $reason, $time, $address, $cid);

            case self::KICK:
                return $this->addBan($type, $sendername, $username, $reason, time(), $address, $cid);
            case self::WARN:
                return $this->addBan($type, $sendername, $username, $reason, time(), $address, $cid);
            default:
                return '§4[!] §cОшибка: неизвестный тип блокировки';
        }

        return 'ok';
    }
}