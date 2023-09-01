<?php declare(strict_types=1);

namespace richen\config;

trait settings {
    public function getDatabaseName(): string {
        return 'serverdata.db';
    }

    public function core(): \richen\fc
    {
        return \richen\fc::getInstance();
    }

    public function off(string $message = null): void
    {
        if ($message) {
            $this->core()->getLogger()->warning($message);
        }

        $this->core()->getServer()->getPluginManager()->disablePlugin($this);
    }
}