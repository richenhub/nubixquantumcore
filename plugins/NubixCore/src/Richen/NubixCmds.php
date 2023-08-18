<?php 

namespace Richen;

use Richen\Custom\NBXPlayer;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

abstract class NubixCmds extends \pocketmine\command\Command {
    private string $commandName;
    public function __construct($commandName, $description = '', $aliases = []) { $this->commandName = mb_strtolower($commandName); parent::__construct($this->commandName, $description, '', $aliases); }
    abstract public function execute(CommandSender $sender, $commandLabel, array $args);
    public function core() { return \Richen\NubixCore::core(); }
    public function serv() { return $this->core()->getServer(); }
    public function lang() { return $this->core()->lang(); }
    public function getOnlinePlayers() { return $this->serv()->getOnlinePlayers(); }
    public function isOp($name): bool { return $this->serv()->isOp($name); }
    public function getCommandName(): string { return $this->commandName; }
    public function getOfflineMessage(string $name) { return $this->lang()->prepare('offline-message', $this->lang()::ERR, [$name]); }
    public function getConsoleUsage() { return $this->lang()->prepare('run-in-game-cmd', $this->lang()::ERR); }
    public function getErrorMessage(): string { return $this->lang()->prepare('unknown-error', $this->lang()::ERR); }
    public function getUsageMessage(string $args): string { return $this->lang()->prepare('usage-message', $this->lang()::WRN, [$this->getCommandName(), $args]); }
    public function isPlayer(CommandSender $sender): bool { return $sender instanceof NBXPlayer; }
    public function isConsole(CommandSender $sender): bool { return $sender instanceof ConsoleCommandSender; }
    public function getPermission(string $sub = ''): string { $commandName = $this->getCommandName(); $sub = ltrim($sub, '.'); return $commandName . ($sub ? '.' . $sub : ''); }
    public function getPlayerByName(string $name): ?Player { $players = []; foreach ($this->getOnlinePlayers() as $player) if (mb_stripos($player->getName(), $name) !== false) $players[] = $player; return count($players) === 1 ? $players[0] : null; }
    public function hasPermission(CommandSender $sender, string $sub = ''): bool { $sub = ($sub !== '' && $sub[0] !== '.') ? '.' . $sub : $sub; return $sender->hasPermission('cmd.' . $this->getCommandName() . $sub) || $this->isOp($sender->getName()) || $sender instanceof ConsoleCommandSender; }
    public function checkPermission(CommandSender $sender, string $sub = '', bool $sendDonate = true): bool {
        $hasPermission = $this->hasPermission($sender, $sub);
        if (!$hasPermission) {
            $sender->sendMessage($this->lang()->prepare('not-perms-cmd', $this->lang()::ERR));
            if ($sendDonate) {
                foreach ($this->core()->conf('groups')->config()->getAll() as $group => $data) {
                    if (in_array($this->getPermission(), $data['perms'])) {
                        $sender->sendMessage($this->lang()->prepare('group-have-perm', $this->lang()::WRN, [$data['prefix']]));
                        return $hasPermission;
                    }
                }
                $sender->sendMessage($this->lang()->prepare('need-upgrade-group', $this->lang()::WRN));
            }
        }
        return $hasPermission;
    }
    protected array $countdown = [];
    public function countdown(CommandSender $sender, int $countdown): bool {
        $command = $this->getCommandName();
        $name = $sender->getName();

        if (isset($this->countdown[$name][$command])) {
            $time = $this->countdown[$name][$command];
            if ($time > time()) {
                $sender->sendMessage($this->lang()::ERR . ' §cНе так часто! Подождите ещё ' . ($time - time()) . ' сек.');
                return false;
            }
        }
        $this->countdown[$name][$command] = time() + $countdown;
        return true;
    }
}