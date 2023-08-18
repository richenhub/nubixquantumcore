<?php 

namespace Richen\Engine;

class Config {
    const TYPES = [ 1 => '.json', 2 => '.yml' ];
    private \pocketmine\utils\Config $config;
    public function __construct(string $path, int $type = 2) { $this->config = new \pocketmine\utils\Config($path . self::TYPES[$type], $type); }
    public function config(): \pocketmine\utils\Config { return $this->config; }
    public function all(): array { return $this->config()->getAll(); }
}