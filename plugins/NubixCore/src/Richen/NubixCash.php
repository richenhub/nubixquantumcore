<?php 

namespace Richen;

use Richen\Custom\NBXMoney;
use pocketmine\utils\TextFormat as C;
use Richen\Engine\Utils;

class NubixCash extends \Richen\Engine\Manager {

    public array $cash = [], $user_ids = [];

    protected $sync = 0;
    
    const MONEYNAMES = [
        1 => [ 'геймкоины', 'геймкоинов', 'геймкоин', 'геймкоина', ],
        2 => [ 'нубиксы', 'нубиксов', 'нубикс', 'нубикса' ],
        3 => [ 'кланиксы', 'кланиксов', 'кланикс', 'кланикса' ],
        4 => [ 'биткоины', 'биткоинов', 'биткоин', 'биткоина' ],
        5 => [ 'дебеты', 'дебетов', 'дебет', 'дебета' ]
    ];
    const MNS_ID = 1, NBS_ID = 2, CLS_ID = 3, BTC_ID = 4, DBT_ID = 5;
    const MNS = '$', NBS = '₵', CNS = '€', BTC = 'B', DBT = '£';

    const TRNSTYPE_SERV2USER = 0;
    const TRNSTYPE_USER2SERV = 1;
    const TRNSTYPE_USER2CLAN = 2;
    const TRNSTYPE_CLAN2USER = 3;
    const TRNSTYPE_CLAN2SERV = 4;
    const TRNSTYPE_SERV2CLAN = 5;
    const TRNSTYPE_USER2USER = 6;
    const TRNSTYPE_CLAN2CLAN = 7;
    const TRNSTYPE_USER_TAX = 8;
    const TRNSNAMES = [
        0 => '{server} > {target_user}',
        1 => '{sender} > {server}',
        2 => '{sender} > {target_clan}',
        3 => '{clan} > {target_user}',
        4 => '{clan} > {server}',
        5 => '{server} > {target_clan}',
        6 => '{sender} > {target_user}',
        7 => '{user_tax}'
    ];

    public function getCurrencyName(int $val, int $id, bool $withName = true) {
        switch ($id) {
            case self::MNS_ID: $name = C::GREEN . Utils::roundvalue($val) . self::MNS . ($withName ? ' ' . C::GREEN . $this->getInclineCurrency($val, $id) : ''); break;
            case self::NBS_ID: $name = C::LIGHT_PURPLE . Utils::roundvalue($val) . self::NBS . ($withName ? ' ' . C::LIGHT_PURPLE . $this->getInclineCurrency($val, $id) : ''); break;
            case self::CLS_ID: $name = C::GOLD . Utils::roundvalue($val) . self::CNS . ($withName ? ' ' . C::GOLD . $this->getInclineCurrency($val, $id) : ''); break;
            case self::BTC_ID: $name = C::AQUA . Utils::roundvalue($val) . self::BTC . ($withName ? ' ' . C::AQUA . $this->getInclineCurrency($val, $id) : ''); break;
            case self::DBT_ID: $name = C::BLUE . Utils::roundvalue($val) . self::DBT . ($withName ? ' ' . C::BLUE . $this->getInclineCurrency($val, $id) : ''); break;
            default: $name = $val . self::MNS;
        }
        return $name;
    }

    public function getInclineCurrency(int $val, int $id) {
        $last = $val % 10;
        $last2 = $val % 100;
        switch (true) {
            case ($last2 >= 11 && $last2 <= 14): return self::MONEYNAMES[$id][1];
            case ($last === 1): return self::MONEYNAMES[$id][2];
            case ($last >= 2 && $last <= 4): return self::MONEYNAMES[$id][3];
            default: return self::MONEYNAMES[$id][1];
        }
    }
    
    public function getUserCash(string $username): NBXMoney {
        if (!isset($this->user_ids[mb_strtolower($username)])) {
            if (mb_strtolower($username) === 'nubix') return $this->getCash(0);
            $id = $this->core()->user()->getUserProp($username, 'id');
            if ($id <= -1) return new NBXMoney(0, -1, 0, 0, 0, 0);
            $this->user_ids[mb_strtolower($username)] = $id;
        }
        return $this->getCash($this->user_ids[mb_strtolower($username)]);
    }

    public function getCash(int $owner_id): NBXMoney {
        if (!isset($this->cash[$owner_id])) {
            $d = $this->data()->get('cash', [['k' => 'owner_id', 'v' => $owner_id]]);
            if (empty($d)) return new NBXMoney(0, $owner_id, 0, 0, 0, 0);
            $d = $d[0];
            $this->cash[$owner_id] = new NBXMoney($d['id'], $d['owner_id'], $d['money'], $d['nubix'], $d['bitcs'], $d['debet']);
        }
        return $this->cash[$owner_id];
    }

    public static function getTransactionName($transaction_type, $sender_id, $target_id) {
        $name = self::TRNSNAMES[$transaction_type] ?? $sender_id . ' > ' . $target_id;
        $arr = [
            '{server}' => 'Сервер',
            '{sender}' => \Richen\NubixCore::user()->getUserPropById($sender_id, 'username'),
            '{target_user}' => \Richen\NubixCore::user()->getUserPropById($target_id, 'username'),
            '{target_clan}' => ($clan = \Richen\NubixCore::clan()->getClanById($target_id * -1 - 2000000000)) ? $clan->getNameTag() : $target_id,
            '{clan}' => ($clan = \Richen\NubixCore::clan()->getClanById($sender_id * -1 - 2000000000)) ? $clan->getNameTag() : $target_id,
            '{user_tax}' => 'Комиссия',
        ];
        return str_replace(array_keys($arr), array_values($arr), $name);
    }

    public static function addTransaction(int $sender_id, int $transaction_type, int $target_id, int $amount, int $amount_id, string $comment) {
        switch ($transaction_type) {
            case self::TRNSTYPE_SERV2USER:
                $sender_id = 0;
                break;
            case self::TRNSTYPE_USER2SERV:
                $target_id = 0;
                break;
            case self::TRNSTYPE_USER2CLAN:
                $target_id = ($target_id + 2000000000) * -1;
                break;
            case self::TRNSTYPE_CLAN2USER:
                $sender_id = ($sender_id + 2000000000) * -1;
                break;
            case self::TRNSTYPE_CLAN2SERV:
                $sender_id = ($sender_id + 2000000000) * -1;
                $target_id = 0;
                break;
            case self::TRNSTYPE_SERV2CLAN:
                $sender_id = 0;
                $target_id = ($target_id + 2000000000) * -1;
                break;
            case self::TRNSTYPE_CLAN2CLAN:
                $sender_id = ($sender_id + 2000000000) * -1;
                $target_id = ($target_id + 2000000000) * -1;
                break;
            case self::TRNSTYPE_USER2USER:
                break;
            case self::TRNSTYPE_USER_TAX:
                break;
            default: throw new \Exception('Неизвестный тип транзакции');
        }
        switch ($amount_id) {
            case self::MNS_ID:
                break;
            case self::NBS_ID:
                break;
            case self::BTC_ID:
                break;
            case self::CLS_ID:
                break;
            case self::DBT_ID:
                break;
            default: throw new \Exception('Неизвестный тип валюты');
        }

        return \Richen\NubixCore::data()->add('transactions', [
            'sender_id' => $sender_id,
            'transaction_type' => $transaction_type,
            'target_id' => $target_id,
            'amount' => $amount,
            'amount_id' => $amount_id,
            'comment' => $comment
        ]);
    }
}