<?php declare(strict_types=1);

namespace richen\engine\source;

class database extends manager {
    protected ?\SQLite3 $db = null;

    public function __construct() {
        $this->db = new \SQLite3($this->path('database', 'data.db'));
    }

    public function conn(): \SQLite3 {
        return $this->db;
    }
}