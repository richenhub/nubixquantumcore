<?php

declare(strict_types=1);

namespace richen\helpers;

trait dbhelper
{
    public function getTables()
    {
        return [
            'users' => [
                'username'  => [$this->vc(20), $this->nn(), $this->uq()],
                'group'     => [$this->vc(20), $this->set('guest')],
                'address'   => [$this->vc(20), $this->nn()],
                'password'  => [$this->vc(50), $this->nn()],
                'lastlogin' => [$this->vc(20), $this->set('', true)],
                'money'     => [$this->num(),  $this->set(0)]
            ]
        ];
    }
    public function vc(int $val): string
    {
        return sprintf('VARCHAR(%s)', min(max($val, 1), 255));
    }
    public function num(): string
    {
        return 'INTEGER';
    }
    public function nn(): string
    {
        return 'NOT NULL';
    }
    public function set($val = 'NULL', $time = false): string
    {
        return sprintf('DEFAULT %s', ($time ? $this->sft() : (is_numeric($val) ? $val : '"' . $val . '"')));
    }
    public function pai(): string
    {
        return 'PRIMARY KEY';
    }
    public function sft(): string
    {
        return '(strftime(\'%s\', \'now\'))';
    }
    public function uq(): string
    {
        return 'UNIQUE';
    }
    public function txt(): string
    {
        return 'TEXT';
    }
    public function bool(): string
    {
        return 'BOOLEAN';
    }
    public function fn(string $field, string $table): string
    {
        return sprintf('FOREIGN KEY (%s) REFERENCES %s(id) ON DELETE CASCADE', $field, $table);
    }

    public function getUnique(string $table): array
    {
        $uniqueFields = [];

        if (isset($this->getTables()[$table])) {
            foreach ($this->getTables()[$table] as $field => $attr) {
                if (in_array('UNIQUE', $attr)) {
                    $uniqueFields[] = $field;
                }
            }
        }

        return $uniqueFields;
    }

    public function isUnique(string $table, string $field): bool
    {
        if (isset($this->getTables()[$table][$field])) {
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
                    if (!isset($value['o'])) {
                        $conditions[$field]['o'] = 0;
                    }

                    if (!isset($value['c'])) {
                        $conditions[$field]['c'] = 0;
                    }
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
        return (!empty($limits)) ? ' LIMIT ' . $this->commaTrim(implode(', ', array_map(static function ($limit) {
            return sprintf('%s', $limit);
        }, $limits))) : null;
    }

    protected function updates(array $updates): string
    {
        $output = '';
        foreach ($updates as $key => $value) {
            if (mb_strtolower($key) === 'id') continue;
            if (is_null($value)) continue;
            if (mb_strtolower($key) === 'password') {
                if (empty($value)) continue;
                $output .= sprintf('`%s` = MD5(%s), ', ($key), $this->value($value));
            } else if (mb_strtolower($key) === 'modify') {
                $output .= sprintf('`%s` = ' . time() . ', ', ($key));
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

        return ' ORDER BY ' . $this->commaTrim(
            implode(', ', array_map(function ($order) {
                return sprintf('%s %s', $order['key'], $order['isd'] ? 'DESC' : 'ASC');
            }, $orders))
        );
    }

    protected function quote(string $text)
    {
        return $this->value(trim(urldecode($text)));
    }
    protected function value($value)
    {
        if ($value === '0') return 0;
        if (empty($value)) return 'NULL';
        return $value;
    }
    protected function spacesRemove(string $text): string
    {
        $text = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $text);
        $text = preg_replace('/,.*$/', '', $text);
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
    public function prepareData(string $dbname, array $data): array
    {
        $tables = $this->getTables();
        if (!isset($tables[$dbname])) return [];
        $arr = [];
        foreach ($tables[$dbname] as $field => $attr) if (isset($data[$field])) $arr[$field] = $data[$field];
        return $arr;
    }
    public function lastError(): string
    {
        return $this->conn()->lastErrorMsg();
    }

    public function createTableQuery()
    {
        $queryList = [];
        foreach ($this->getTables() as $table => $fields) {
            $fields = array_merge(
                ['id' => [$this->num(), $this->pai()]],
                $fields,
                ['created'  => [$this->num(), $this->set(0, true)]],
                ['modified' => [$this->num(), $this->set(0, true)]]
            );
            // $fields = [
            //     'id' => [$this->num(), $this->pai()],
            //     ...$fields,
            //     'created'  => [$this->num(), $this->set(0, true)],
            //     'modified' => [$this->num(), $this->set(0, true)]
            // ];
            $query_fields = [];
            foreach ($fields as $field => $types) {
                if ($field === 'foreign') {
                } else {
                    $query_fields[] = '`' . $field . '` ' . implode(' ', $types);
                }
            }
            $queryList[] = 'CREATE TABLE IF NOT EXISTS ' . $table . ' (' . implode(', ', $query_fields) . ');';
        }
        return $queryList;
    }
}
