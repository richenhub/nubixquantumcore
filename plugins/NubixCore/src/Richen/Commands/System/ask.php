<?php 

namespace Richen\Commands\System;

use Richen\Engine\Filter;

class ask extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'Вопросы и жалобы', ['a']); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (count($args) < 3) return $sender->sendMessage($this->getUsageMessage('[вопрос или жалоба] §e- не меньше 3 слов.'));
        $message = implode(' ', $args);
        if (!Filter::isAllowed($message)) return $sender->sendMessage($this->lang()::ERR . ' §cНельзя использовать нецензурные выражения!');
        if (!$this->countdown($sender, 60)) return;
        $vk_message = '[ASK] Вопрос от игрока ' . $sender->getName() . ': ' . $message;
        if ($this->core()->vcom()->sendMessage($vk_message, true, true)) {
            $sender->sendMessage('§6[§eАSK§6] §fВы отправили сообщение §e' . $sender->getName() . '§f: §7' . $message);
        } else {
            $sender->sendMessage($this->lang()::ERR . ' §cСообщение не было отправлено! Попробуйте ещё раз');
        }
    }
}



