<?php 

namespace Richen;

class NubixData extends Engine\Manager {
    private array $operationType = ['=', '<>', '<', '>', 'IS NULL', 'IS NOT NULL', 'LIKE', '>=', '<='];
    private array $concatinationType = ['AND', 'OR'];
    public static \SQLite3 $db;
    public function __construct(\SQLite3 $db) { self::$db = $db; 
        $this->tables = [
            'users' => [
                'id' =>         [$this->integer(), $this->primaryai()],
                'username' =>   [$this->varchar(20), $this->notnull(), $this->unique()],
                'group' =>      [$this->varchar(20), $this->default('guest')],
                'address' =>    [$this->varchar(20), $this->notnull()],
                'password' =>   [$this->varchar(255), $this->notnull()],
                'register' =>   [$this->varchar(20), $this->default('', true)],
                'lastlogin' =>  [$this->varchar(20), $this->default('', true)],
                'modify' =>     [$this->varchar(20), $this->default('', true)],
            ],
            'cash' => [
                'id' =>         [$this->integer(), $this->primaryai()],
                'owner_id' =>   [$this->integer(), $this->notnull(), $this->unique()],
                'money' =>      [$this->integer(), $this->default(0)],
                'nubix' =>      [$this->integer(), $this->default(0)],
                'debet' =>      [$this->integer(), $this->default(0)],
                'bitcs' =>      [$this->integer(), $this->default(0)],
            ],
            'stats' => [
                'id' =>         [$this->integer(), $this->primaryai()],
                'user_id' =>    [$this->integer(), $this->notnull(), $this->unique()],
                'kills' =>      [$this->integer(), $this->default(0)],
                'death' =>      [$this->integer(), $this->default(0)],
                'online' =>     [$this->integer(), $this->default(0)],
                'place' =>      [$this->integer(), $this->default(0)],
                'break' =>      [$this->integer(), $this->default(0)],
                'messages' =>   [$this->integer(), $this->default(0)],
            ],
            'bansystem' => [
                'id' =>         [$this->integer(), $this->primaryai()],
                'username' =>   [$this->varchar(20), $this->notnull()],
                'banip' =>      [$this->integer(), $this->default(0)],
                'ban' =>        [$this->integer(), $this->default(0)],
                'bancid' =>     [$this->integer(), $this->default(0)],
                'cid' =>        [$this->integer(), $this->default(0)],
                'jail' =>       [$this->integer(), $this->default(0)],
                'mute' =>       [$this->integer(), $this->default(0)],
                'kick' =>       [$this->integer(), $this->default(0)],
                'warn' =>       [$this->integer(), $this->default(0)],
                'sender' =>     [$this->varchar(20), $this->notnull()],
                'reason' =>     [$this->varchar(50), $this->notnull()],
                'address' =>    [$this->varchar(20), $this->notnull()],
                'status' =>     [$this->boolean(), $this->notnull(), $this->default('TRUE')],
                'comment' =>    [$this->varchar(50), $this->default('')],
                'modify' =>     [$this->integer(), $this->default('', true)]
            ],
            'region' => [
                'id' =>         [$this->integer(), $this->primaryai()],
                'region' =>     [$this->varchar(20), $this->notnull(), $this->unique()],
                'owner' =>      [$this->varchar(20), $this->notnull()],
                'x1' =>         [$this->integer(), $this->notnull()],
                'y1' =>         [$this->integer(), $this->notnull()],
                'z1' =>         [$this->integer(), $this->notnull()],
                'x2' =>         [$this->integer(), $this->notnull()],
                'y2' =>         [$this->integer(), $this->notnull()],
                'z2' =>         [$this->integer(), $this->notnull()],
                'level' =>      [$this->varchar(20), $this->notnull()],
                'sell' =>       [$this->integer(), $this->default(0)],
                'created' =>    [$this->integer(), $this->default('', true)],
                'modify' =>     [$this->integer(), $this->default('', true)]
            ],
            'member' => [
                'id' =>         [$this->integer(), $this->primaryai()],
                'username' =>   [$this->varchar(20), $this->notnull()],
                'region_id' =>  [$this->integer(), $this->notnull()],
                'modify' =>     [$this->integer(), $this->default('', true)],
            ],
            'flags' => [
                'id' =>         [$this->integer(), $this->primaryai()],
                'region_id' =>  [$this->integer(), $this->notnull()],
                'flag' =>       [$this->varchar(20), $this->notnull()],
                'status' =>     [$this->boolean(), $this->notnull()],
                'foreign' =>    ['region_id', 'region'],
            ],
            'transactions' => [
                'id' =>         [$this->integer(), $this->primaryai()],
                'sender_id' =>  [$this->integer(), $this->notnull()],
                'transaction_type' => [$this->integer(), $this->notnull()],
                'target_id' =>  [$this->integer(), $this->default(0)],
                'amount' =>     [$this->integer(), $this->notnull()],
                'amount_id' =>  [$this->integer(), $this->notnull()],
                'comment' =>    [$this->varchar(255), $this->notnull()],
                'created' =>    [$this->integer(), $this->default('', true)],
            ],
            'clans' => [
                'id' =>         [$this->integer(), $this->primaryai()],
                'clan' =>       [$this->varchar(8), $this->notnull(), $this->unique()],
                'owner' =>      [$this->varchar(8), $this->notnull(), $this->unique()],
                'lvl' =>        [$this->integer(), $this->default(1)],
                'exp' =>        [$this->integer(), $this->default(0)],
                'color' =>      [$this->integer(), $this->default(0)],
                'home' =>       [$this->varchar(255), $this->default()],
                'created' =>    [$this->integer(), $this->default('', true)],
                'modify' =>     [$this->integer(), $this->default('', true)],
            ],
            'clanmembers' => [
                'id' =>         [$this->integer(), $this->primaryai()],
                'clan_id' =>    [$this->integer(), $this->notnull()],
                'username' =>   [$this->varchar(20), $this->notnull(), $this->unique()],
                'rank' =>       [$this->integer(), $this->default(1)],
                'created' =>    [$this->integer(), $this->default('', true)],
                'modify' =>     [$this->integer(), $this->default('', true)],
            ],
            'clanstats' => [
                'id' =>         [$this->integer(), $this->primaryai()],
                'clan_id' =>    [$this->integer(), $this->notnull(), $this->unique()],
                'kills' =>      [$this->integer(), $this->default(0)],
                'death' =>      [$this->integer(), $this->default(0)],
                'online' =>     [$this->integer(), $this->default(0)],
            ],
            'cases' => [
                'id' =>         [$this->integer(), $this->primaryai()],
                'user_id' =>    [$this->integer(), $this->notnull()],
                'type' =>       [$this->integer(), $this->notnull()],
                'price' =>      [$this->integer(), $this->notnull()],
                'result' =>     [$this->varchar(255), $this->default()],
                'status' =>     [$this->varchar(255), $this->default(1)],
                'created' =>    [$this->integer(), $this->default('', true)],
                'modify' =>     [$this->integer(), $this->default('', true)],
            ],
        ];
        $this->createTables();
    }
    public function conn(): \SQLite3 { return self::$db; }

    const VARCHAR = 'VARCHAR(%s)';
    const INTEGER = 'INTEGER';
    const NOTNULL = 'NOT NULL';
    const UNIQUE = 'UNIQUE';
    const DEFAULT = 'DEFAULT %s';
    const TEXT = 'TEXT';
    const PRIMARY_AI = 'PRIMARY KEY';
    const STRFTIME = '(strftime(\'%s\', \'now\'))';
    const BOOLEAN = 'BOOLEAN';
    const FOREIGN = 'FOREIGN KEY (%s) REFERENCES %s(id) ON DELETE CASCADE';

    public function varchar(int $val): string { return sprintf(self::VARCHAR, min(max($val, 1), 255)); }
    public function integer(): string { return self::INTEGER; }
    public function notnull(): string { return self::NOTNULL; }
    public function default($val = 'NULL', $time = false): string { return sprintf(self::DEFAULT, ($time ? $this->strftime() : (is_numeric($val) ? $val : '"' . $val . '"'))); }
    public function primaryai(): string { return self::PRIMARY_AI; }
    public function strftime(): string { return self::STRFTIME; }
    public function unique(): string { return self::UNIQUE; }
    public function text(): string { return self::TEXT; }
    public function boolean(): string { return self::BOOLEAN; }
    public function foreign(string $field, string $table): string { return sprintf(self::FOREIGN, $field, $table); }

    public function getUnique(string $table): array {
        $uniqueFields = [];
        if (isset($this->tables[$table])) {
            foreach ($this->tables[$table] as $field => $attr) {
                if (in_array('UNIQUE', $attr)) {
                    $uniqueFields[] = $field;
                }
            }
        }
        return $uniqueFields;
    }

    public function isUnique(string $table, string $field): bool {
        if (isset($this->tables[$table][$field])) {
            $fieldAttr = $this->tables[$table][$field];
            return in_array('UNIQUE', $fieldAttr);
        }
        return false;
    }

    public array $tables = [];

    public function createTables() {
        foreach ($this->tables as $table => $fields) {
            $query_fields = [];
            foreach ($fields as $field => $types) {
                if ($field === 'foreign') {

                } else {
                    $query_fields[] = '`' . $field . '` ' . implode(' ', $types);
                }
            }
            $sql = 'CREATE TABLE IF NOT EXISTS ' . $table . ' (' . implode(', ', $query_fields) . ');';
            if (!$this->conn()->query($sql)) {
                throw new \Exception("Error creating table: " . $this->conn()->lastErrorMsg());
            }
        }
    }

    public function get(string $dbname, array $conditions = [], array $orders = [], array $limits = []): array {
        $stmt = $this->conn()->query('SELECT * FROM ' . $dbname . $this->conditions($conditions) . $this->orders($orders) . $this->limits($limits));
        $data = [];
        while ($row = $stmt->fetchArray(SQLITE3_ASSOC)) $data[] = $row;
        return $data;
    }

    public function count(string $dbname, array $conditions = []): int {
        $sql = 'SELECT COUNT(*) as count FROM ' . $dbname . $this->conditions($conditions);
        $stmt = $this->conn()->prepare($sql);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row['count'];
    }

    public function update(string $dbname, array $data, array $conditions): void {
        try {
            $this->conn()->prepare('UPDATE ' . $dbname . ' SET ' . $this->updates($this->preparedata($dbname, $data)) . $this->conditions($conditions))->execute();
        } catch (\Exception $e) {
            $this->core()->getLogger()->warning($this->conn()->lastErrorMsg());
        }
    }

    public function add(string $dbname, array $data): int {
        try {
            $stmt = $this->conn()->prepare('INSERT OR IGNORE INTO ' . $dbname . $this->prepareAdd($dbname, $data));
            $stmt->execute();
        } catch (\Exception $e) {
            $this->core()->getLogger()->warning($this->conn()->lastErrorMsg());
        }
        return $this->conn()->lastInsertRowID();
    }

    public function delete(string $dbname, array $conditions): void {
        try {
            $this->conn()->prepare(sprintf('DELETE FROM %s%s', $dbname, $this->conditions($conditions)))->execute();
        } catch (\Exception $e) {
            $this->core()->getLogger()->warning($this->conn()->lastErrorMsg());
        }
    }

    protected function conditions(array $conditions)
    {
        if (!is_null($conditions) && count($conditions) >= 1) {
            foreach ($conditions as $field => $value) {
                if (count($conditions) >= 1) {
                    if (!isset($value['o'])) $conditions[$field]['o'] = 0;
                    if (!isset($value['c'])) $conditions[$field]['c'] = 0;
                }
            }
            $operationType = $this->operationType;
            $concatinationType = $this->concatinationType;
            return sprintf(" WHERE %s", $this->concatinationTrim(implode('', array_map(static function ($condition) use ($operationType, $concatinationType) {
                if (!empty($condition['k'])) {
                    $operation = $operationType[$condition['o']];
                    $concatination = $concatinationType[$condition['c']];
                    switch ($condition['o']) {
                        case 4:
                        case 5:
                            return sprintf('%s %s %s ', $concatination, ($condition['k']), $operation);
                        case 6:
                            return sprintf('%s %s LIKE \'%%%s%%\' ', $concatination, ($condition['k']), $condition['v']);
                    }
                    $value = sprintf('\'%s\'', $condition['v']);
                    if (is_numeric($condition['v']) || is_bool($condition['v'])) {
                        $value = sprintf('%s', $condition['v']);
                    }
                    if (strtolower($condition['k']) === 'password') {
                        $value = sprintf('MD5(\'%s\')', $condition['v']);
                    }
                    return sprintf('%s %s %s %s ', $concatination, ($condition['k']), $operation, $value);
                }
                return false;
            }, $conditions))));
        }
        return false;
    }

    public function limits(array $limits): ?string { return (!empty($limits)) ? ' LIMIT ' . $this->commaTrim(implode(', ', array_map(static function ($limit) { return sprintf('%s', $limit); }, $limits))) : null; }

    protected function updates(array $updates): string {
        $output = '';
        foreach ($updates as $key => $value) {
            if (mb_strtolower($key) === 'id') continue;
            if (is_null($value)) continue;
            if (mb_strtolower($key) === 'password') {
                if (empty($value)) continue;
                $output .= sprintf('`%s` = MD5(%s), ', ($key), $this->value($value));
            } else if (mb_strtolower($key) === 'modify') {
                $output .= sprintf('`%s` = '.time().', ', ($key));
            } else if ($value === 0) {
                $output .= sprintf('`%s` = %s, ', ($key), $value);
            } elseif (empty($value)) {
                $output .= sprintf('`%s` = NULL, ', ($key));
            } else {
                $output .= sprintf('`%s` = %s, ', ($key), "\"" . (is_numeric($value) ? $this->value($value) : $this->value($value)) . "\""/*$this->quote($value)*/);
            }
        }
        return $this->commaTrim($output);
    }

    protected function orders(array $orders): string { if (empty($orders)) return ''; return ' ORDER BY ' . $this->commaTrim(implode(', ', array_map(function ($order) { return sprintf('%s %s', $order['key'], $order['isd'] ? 'DESC' : 'ASC'); }, $orders))); }
    protected function quote(string $text) { return $this->value(trim(urldecode($text))); }
    protected function value($value) { if ($value === '0') return 0; if (empty($value)) return 'NULL'; return $value; }
    protected function spacesRemove(string $text): string { $text = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $text); $text = preg_replace('/,.*$/', '', $text); return preg_replace('/\..*$/', '', $text); }
    public function commaTrim(string $text): string { return rtrim(trim($text), ','); }
    public function concatinationTrim(string $text): string { return trim(preg_replace('/^(AND|OR)/', '', trim($text))); }

    public function prepareAdd(string $dbname, array $data): string {
        $data = $this->preparedata($dbname, $data);
        $excluded = $this->getUnique($dbname)[0] ?? null;
        $keys = array_keys($data);
        $result = '(' . implode(',', $keys) . ') VALUES (\'' . implode('\',\'', array_values($data)) . '\')';
        if ($excluded) {
            $result .= ' ON CONFLICT(' . $excluded . ')
            DO UPDATE SET ' . (static function () use ($keys): string { $excl = []; foreach ($keys as $key) $excl[] = $key . '=excluded.' . $key; return implode(', ', $excl); })();
        }
        return $result ?? '';
    }

    public function prepareData(string $dbname, array $data): array { $tables = $this->tables; if (!isset($tables[$dbname])) return []; $arr = []; foreach ($tables[$dbname] as $field => $attr) if (isset($data[$field])) $arr[$field] = $data[$field]; return $arr; }
    public function lastError(): string { return $this->conn()->lastErrorMsg(); }
}