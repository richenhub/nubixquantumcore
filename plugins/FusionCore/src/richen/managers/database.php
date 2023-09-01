<?php declare(strict_types=1);

namespace richen\managers;

class database extends manager
{
    use \richen\config\tables;

    public \SQLite3 $db;

    public function __construct()
    {
        $this->db = $this->core()->db();

        $this->createTableExact();
    }

    public function conn(): \SQLite3
    {
        return $this->db;
    }

    public function createTableExact()
    {
        $query = $this->getCreateTablesQuery();

        $this->conn()->exec('BEGIN');

        try {
            foreach ($query as $sql)
            {
                if (!$this->conn()->query($sql)) {
                    throw new \Exception('Error creating table: ' . $this->conn()->lastErrorMsg());
                }
            }

            $this->conn()->exec('COMMIT');
        } catch (\Exception $e) {
            $this->conn()->exec('ROLLBACK');

            throw $e;
        }
    }

    private function executeQuery(string $query): array
    {
        $stmt = $this->conn()->query($query);
        $data = [];

        while ($row = $stmt->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    private function executeScalarQuery(string $query)
    {
        $stmt = $this->conn()->query($query);

        return $stmt->fetchArray(SQLITE3_ASSOC)['count'] ?? 0;
    }

    private function executeNonQuery(string $query)
    {
        try {
            $this->conn()->exec($query);
        } catch (\Exception $e) {
            $this->core()->getLogger()->warning($this->conn()->lastErrorMsg());
        }
    }

    public function get(string $dbname, array $conditions = [], array $orders = [], array $limits = []): array
    {
        $query = 'SELECT * FROM ' . $dbname . $this->conditions($conditions) . $this->orders($orders) . $this->limits($limits);
        
        return $this->executeQuery($query);
    }

    public function count(string $dbname, array $conditions = []): int
    {
        $query = 'SELECT COUNT(*) as count FROM ' . $dbname . $this->conditions($conditions);

        return (int)$this->executeScalarQuery($query);
    }

    public function update(string $dbname, array $data, array $conditions): void
    {
        $sql = 'UPDATE ' . $dbname . ' SET ' . $this->updates($this->preparedata($dbname, $data)) . $this->conditions($conditions);

        $this->executeNonQuery($sql);
    }

    public function add(string $dbname, array $data): int
    {
        $sql = 'INSERT OR IGNORE INTO ' . $dbname . $this->prepareAdd($dbname, $data);

        $this->executeNonQuery($sql);
    
        return $this->conn()->lastInsertRowID();
    }

    public function delete(string $dbname, array $conditions): void
    {
        $sql = 'DELETE FROM ' . $dbname . $this->conditions($conditions);

        $this->executeNonQuery($sql);
    }
}