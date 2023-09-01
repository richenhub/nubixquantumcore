<?php declare(strict_types=1);

namespace richen\engine\source;

class query extends manager {
    use \richen\helpers\dbhelper;

    public database $db;

    public function __construct(database $db) {
        $this->db = $db;
    }

    public function conn() {
        return $this->db->conn();
    }

    public function createTableExact() {
        $query = $this->createTableQuery();

        $this->conn()->exec('BEGIN');

        try {
            foreach ($query as $sql) {
                if (!$this->conn()->query($sql)) {
                    throw new \Exception("Error creating table: " . $this->conn()->lastErrorMsg());
                }
            }

            $this->conn()->exec('COMMIT');
        } catch (\Exception $e) {
            $this->conn()->exec('ROLLBACK');
            throw $e;
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
            $this->log($this->conn()->lastErrorMsg());
        }
    }

    public function add(string $dbname, array $data): int {
        try {
            $stmt = $this->conn()->prepare('INSERT OR IGNORE INTO ' . $dbname . $this->prepareAdd($dbname, $data));
            $stmt->execute();
        } catch (\Exception $e) {
            $this->log($this->conn()->lastErrorMsg());
        }
        return $this->conn()->lastInsertRowID();
    }

    public function delete(string $dbname, array $conditions): void {
        try {
            $this->conn()->prepare(sprintf('DELETE FROM %s%s', $dbname, $this->conditions($conditions)))->execute();
        } catch (\Exception $e) {
            $this->log($this->conn()->lastErrorMsg());
        }
    }
    
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

    public function prepareData(string $dbname, array $data): array { $tables = $this->getTables(); if (!isset($tables[$dbname])) return []; $arr = []; foreach ($tables[$dbname] as $field => $attr) if (isset($data[$field])) $arr[$field] = $data[$field]; return $arr; }
   
}