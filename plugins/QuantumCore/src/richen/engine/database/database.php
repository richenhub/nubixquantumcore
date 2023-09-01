<?php 

declare(strict_types=1);

namespace richen\engine\database;

class database extends manager {
    use \richen\helpers\dbhelper;
    private \SQLite3 $db;
    private string $dbName;
    private string $fileType;
    private array $tables = [];

    public function __construct(string $dbName = 'data', string $fileType = '.db') {
        $this->dbName = $dbName;
        $this->fileType = $fileType;
        $this->db = new \SQLite3($this->getPath('database', $this->dbName . $this->fileType));
    }

    public function conn(): \SQLite3 {
        return $this->db;
    }
}