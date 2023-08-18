<?php 

namespace Richen\Commands;

use pocketmine\command\ConsoleCommandSender;

class help extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Помощь по командам'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;

        $commands = $this->serv()->getCommandMap()->getCommands();

        usort($commands, function($cmd1, $cmd2) use ($sender) {
            $cmd1_available = $this->hasCmdPermission($sender, $cmd1);
            $cmd2_available = $this->hasCmdPermission($sender, $cmd2);
            if ($cmd1_available && !$cmd2_available) return -1;
            elseif (!$cmd1_available && $cmd2_available) return 1;
            else return 0;
        });

        $commands = array_unique($commands);

        $page = max(1, min(isset($args[0]) && is_numeric($args[0]) ? intval($args[0]) : 1, ceil(count($commands) / 10)));
        $dataPage = array_slice($commands, ($page - 1) * 10, 10);
        $sender->sendMessage('§6[!] §2Команды сервера§7. Всего §6' . count($commands) . '§7. Показана страница §e' . $page . '§7:');
        $i = ($page - 1) * 10 + 1;
        foreach ($dataPage as $key => $cmd) {
            $available = 'Консоль';
            foreach ($this->core()->conf('groups')->config()->getAll() as $group => $data) {
                if (in_array($cmd->getPermission(), $data['perms'])) {
                    $available = $data['prefix'];
                    break;
                }
            }
            $sender->sendMessage('§2' . $i++ . ') §a/' . $cmd->getName() . ' §8- §f' . (is_string($cmd->getDescription()) ? $cmd->getDescription() : '§6Нет описания') . ' §f- доступно от §e' . $available);
        }
    }

    private function hasCmdPermission($sender, $command) {
        return $sender->hasPermission($command->getName()) || $sender instanceof ConsoleCommandSender || $this->serv()->isOp($sender->getName());
    }
}