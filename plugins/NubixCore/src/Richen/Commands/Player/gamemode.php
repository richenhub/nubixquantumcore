<?php 

namespace Richen\Commands\Player;

use pocketmine\Player;
use Richen\Custom\NBXPlayer;

class gamemode extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Изменить режим игры', ['gm']); }
    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        $player = $sender;
        if (!isset($args[0])) {
            if (!$this->isConsole($sender)) $sender->sendMessage($this->getUsageMessage('[режим]'));
            if ($this->checkPermission($sender, 'other')) {
                $sender->sendMessage($this->getUsageMessage('[режим] [игрок] - выдача другому игроку'));
            }
            return;
        }
        $gamemode = $args[0];
        if (isset($args[1]) && (($this->hasPermission($sender, '.other') || $this->isConsole($sender)))) {
            $name = $args[1];
            $player = $this->getPlayerByName($name);
            if (!$player) {
                $sender->sendMessage($this->getOfflineMessage($name));
            } else {
                if (!$this->toggleGamemode($player, $gamemode)) {
                    $sender->sendMessage($this->lang()->prepare('not-found-gamemode', $this->lang()::SUC, [$player->getName()]));
                } elseif ($sender !== $player) {
                    $sender->sendMessage('§2[!] §7Режим игрока §6' . $player->getName() . ' §7изменен на ' . $this->parseGameMode($player->getGamemode())[1]);
                }
            }
        } else {
            if ($this->isConsole($sender)) return $sender->sendMessage($this->getConsoleUsage());
            if (!$this->toggleGamemode($player, $gamemode)) {
                $sender->sendMessage($this->lang()->prepare('not-found-gamemode', $this->lang()::SUC, [$player->getName()]));
            }
        }
    }

    public function getGamemodeName($gamemode) {
        return $this->lang()->prepare('gamemode-' . $gamemode) ?? false;
    }

    public function parseGameMode($input): array {
        if (is_numeric($input)) $input = (int) $input;
        switch ($input) {
            case 0:
            case 'survival':
            case 'выживание':
            case 's':
                return [0, $this->lang()->prepare('gamemode-0')];
            case 1:
            case 'creative':
            case 'c':
            case 'креатив':
                return [1, $this->lang()->prepare('gamemode-1')];
            case 2:
            case 'adventure':
            case 'a':
            case 'приключение':
                return [2, $this->lang()->prepare('gamemode-2')];
            case 3:
            case 'spectator':
            case 'sp':
            case 'наблюдение':
                return [3, $this->lang()->prepare('gamemode-3')];
            default:
                return [0, 'Unknown gamemode'];
        }
    }

    public function toggleGamemode(Player $player, string $type): bool {
        $gamemode = $this->parseGameMode($type);
        $player->setGamemode($gamemode[0]);
        $player->sendMessage($this->lang()->prepare('gamemode-toggle', $this->lang()::SUC, [$gamemode[1]]));
        return true;
    }
}