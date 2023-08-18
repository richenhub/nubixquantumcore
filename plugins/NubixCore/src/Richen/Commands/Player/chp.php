<?php 

namespace Richen\Commands\Player;

class chp extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Смена пароля'); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof \Richen\Custom\NBXPlayer) return $sender->sendMessage($this->getConsoleUsage());
        if (count($args) !== 3) return  $sender->sendMessage($this->getUsageMessage('[текущий_пароль] [новый_пароль] [повтор_пароля]'));
        if (!$sender->isauth() || !$sender->isreg()) return $sender->sendMessage($this->lang()::ERR . ' §cВы не авторизованы');
        if ($args[1] !== $args[2]) $sender->sendMessage($this->lang()::ERR . ' §cНовый пароль и повтор пароля не совпадают');
        if (!password_verify($args[0], $sender->getPassword())) return $sender->sendMessage($this->lang()::ERR . ' §cВы ввели неверный пароль от аккаунта');
        $this->core()->user()->setUserProp($sender->getLowerCaseName(), ['password' => password_hash($args[1], PASSWORD_DEFAULT)]);
        $sender->sendMessage($this->lang()::SUC . ' §aВаш пароль обновлен!');
    }
}