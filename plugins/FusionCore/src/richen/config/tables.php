<?php declare(strict_types=1);

namespace richen\config;

trait tables {
    public function getTables()
    {
        return [
            'users' => [
                'nick' =>       [$this->varchar(20),    $this->notnull(), $this->unique()],
                'group' =>      [$this->varchar(20),    $this->default('guest')],
                'address' =>    [$this->varchar(20),    $this->default()],
                'password' =>   [$this->varchar(255),   $this->default()],
                'money' =>      [$this->integer(),      $this->default(0)],
                'registered' => [$this->integer(),      $this->default('', true)],
                'logined' =>    [$this->integer(),      $this->default('', true)],
            ],
        ];
    }

    public function getCreateTablesQuery(): array
    {
        $query_list = [];

        foreach ($this->getTables() as $table => $fields)
        {
            $query_fields = [];

            $fields = array_merge(
                [
                    'id' => [
                        $this->integer(),
                        $this->primaryai()
                    ]
                ],
                $fields,
                [
                    'created' => [
                        $this->integer(),
                        $this->default(0, true)
                    ]
                ],
                [
                    'modified' => [
                        $this->integer(),
                        $this->default(0, true)
                    ]
                ]
            );

            foreach ($fields as $field => $types)
            {
                if ($field === 'foreign') {

                } else {
                    $query_fields[] = '`' . $field . '` ' . implode(' ', $types);
                }
            }

            $query_list[] = 'CREATE TABLE IF NOT EXISTS ' . $table . ' (' . implode(', ', $query_fields) . ');';
        }

        return $query_list;
    }

    public function varchar(int $val): string { return sprintf('VARCHAR(%s)', min(max($val, 1), 255)); }
    public function integer(): string { return 'INTEGER'; }
    public function notnull(): string { return 'NOT NULL'; }
    public function default($val = 'NULL', bool $time = false): string { return sprintf('DEFAULT %s', ($time ? $this->strftime() : (is_numeric($val) ? $val : '"' . $val . '"'))); }
    public function primaryai(): string { return 'PRIMARY KEY'; }
    public function strftime(): string { return '(strftime(\'%s\', \'now\'))'; }
    public function unique(): string { return 'UNIQUE'; }
    public function text(): string { return 'TEXT'; }
    public function boolean(): string { return 'BOOLEAN'; }
    public function foreign(string $field, string $table): string { return sprintf('FOREIGN KEY (%s) REFERENCES %s(id) ON DELETE CASCADE', $field, $table); }

    public function getUnique(string $table): array
    {
        $uniqueFields = [];
        if (isset($this->getTables()[$table])) {
            foreach ($this->getTables()[$table] as $field => $attr)
            {
                if (in_array('UNIQUE', $attr)) {
                    $uniqueFields[] = $field;
                }
            }
        }

        return $uniqueFields;
    }

    public function isUnique(string $table, string $field): bool
    {
        if (isset($this->getTables()[$table][$field]))
        {
            $fieldAttr = $this->getTables()[$table][$field];

            return in_array('UNIQUE', $fieldAttr);
        }
        return false;
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

    public function limits(array $limits): ?string
    {
        return (!empty($limits)) ? ' LIMIT ' . $this->commaTrim(implode(', ', array_map(static function ($limit) { return sprintf('%s', $limit); }, $limits))) : null;
    }

    protected function updates(array $updates): string
    {
        $output = '';

        foreach ($updates as $key => $value)
        {
            if (mb_strtolower($key) === 'id' || is_null($value)) {
                continue;
            }

            if (mb_strtolower($key) === 'password') {
                if (empty($value)) {
                    continue;
                }

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

    protected function orders(array $orders): string
    {
        if (empty($orders)) {
            return '';
        }
        
        return ' ORDER BY ' . $this->commaTrim(implode(', ', array_map(function ($order) { return sprintf('%s %s', $order['key'], $order['isd'] ? 'DESC' : 'ASC'); }, $orders)));
    }

    protected function quote(string $text)
    {
        return $this->value(trim(urldecode($text)));
    }

    protected function value($value)
    {
        if ($value === '0') {
            return 0;
        }
        
        if (empty($value)) {
            return 'NULL';
        }
        
        return $value;
    }

    protected function spacesRemove(string $text): string
    {
        $text = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $text); $text = preg_replace('/,.*$/', '', $text);
        
        return preg_replace('/\..*$/', '', $text);
    }

    public function commaTrim(string $text): string
    {
        return rtrim(trim($text), ',');
    }

    public function concatinationTrim(string $text): string
    {
        return trim(preg_replace('/^(AND|OR)/', '', trim($text)));
    }

    public function prepareAdd(string $dbname, array $data): string
    {
        $data = $this->prepareData($dbname, $data);
        $excluded = $this->getUnique($dbname)[0];
        $keys = array_keys($data);
        $result = '(' . implode(',', $keys) . ') VALUES (\'' . implode('\',\'', array_values($data)) . '\')';

        if ($excluded) {
            $result .= ' ON CONFLICT(' . $excluded . ') DO UPDATE SET ' . (
                static function () use ($keys): string
                {
                    $excl = [];
                    
                    foreach ($keys as $key)
                    {
                        $excl[] = $key . '=excluded.' . $key;
                    }
                    
                    return implode(', ', $excl);
                }
            )();
        }

        return $result;
    }

    public function prepareData(string $dbname, array $data): array
    {
        $tables = $this->getTables();
        
        if (!isset($tables[$dbname])) {
            return [];
        }
        
        $arr = [];
        
        foreach (array_keys($tables[$dbname]) as $field)
        {
            if (isset($data[$field])) {
                $arr[$field] = $data[$field];
            }
        }
        
        return $arr;
    }
}