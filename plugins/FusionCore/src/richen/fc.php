<?php declare(strict_types=1);

namespace richen;

class fc extends \pocketmine\plugin\PluginBase
{
    use config\settings;

    public static fc $instance;

    public function onEnable()
    {
        self::$instance = $this;

        $this->getServer()->getPluginManager()->registerEvents(new events\event(), $this);

        if (!is_dir($this->getDataFolder())) {
            @mkdir($this->getDataFolder());
        }

        try {
            $this->db = new \SQLite3($this->getDataFolder() . $this->getDatabaseName());
            if (!$this->db) {
                $this->off('База данных не загружена');

                return;
            }

            $this->registerManager();
        } catch (\Exception $e) {
            $this->off('Ошибка во время инициализации БД: ' . $e->getMessage());

            return;
        }
    }

    public static function getInstance(): fc {
        return self::$instance;
    }

    private ?\SQLite3 $db = null;

    public function db(): \SQLite3
    {
        return $this->db;
    }

    private ?\richen\managers\manager $manager = null;

    public function registerManager()
    {
        $this->manager = new \richen\managers\manager();
    }
}