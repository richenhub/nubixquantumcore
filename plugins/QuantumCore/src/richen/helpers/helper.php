<?php

declare(strict_types=1);

namespace richen\helpers;

define('SLASH', '/');

trait helper
{
    public function server(): \pocketmine\Server
    {
        return \pocketmine\Server::getInstance();
    }

    public function log(string $message): void
    {
        $this->server()->getLogger()->info($message);
    }

    public function path(string $folderName, string $fileName, bool $create_if_not_exists = true): ?string
    {
        $path = $this->server()->getDataPath() . SLASH . $folderName . SLASH;

        if (!is_dir($path)) {
            if ($create_if_not_exists) {
                @mkdir($path, 0777);
            } else {
                return null;
            }
        }

        return $path . $fileName;
    }
}
