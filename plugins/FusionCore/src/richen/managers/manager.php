<?php declare(strict_types=1);

namespace richen\managers;

class manager
{

    public function core(): \richen\fc
    {
        if (!$this->fc) {
            $this->fc = \richen\fc::getInstance();
        }

        return $this->fc;
    }

    private ?\richen\fc $fc = null;
    public function __construct() {
        $this->registerManagers();
    }

    public function isCustomPlayer(\pocketmine\Player $player): bool
    {
        return $player instanceof \richen\custom\customplayer;
    }

    /**
     * Managers
     */

    private ?database $database = null;
    private ?easyauth $easyauth = null;

    public function registerManagers(): void
    {
        $this->database = new database();
        $this->easyauth = new easyauth();
    }

    public function database(): \richen\managers\database
    {
        return $this->database;
    }

    public function easyauth(): \richen\managers\easyauth
    {
        return $this->easyauth;
    }

    public function createObject(array $array): ?\richen\engine\objectdata
    {
        return new \richen\engine\objectdata($array);
    }
}