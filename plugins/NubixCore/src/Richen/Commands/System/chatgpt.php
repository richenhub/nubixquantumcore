<?php 

namespace Richen\Commands\System;

use Richen\Engine\Filter;

class chatgpt extends \Richen\NubixCmds {
    public function __construct($name) { parent::__construct($name, 'ChatGPT', ['cg']); }

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (count($args) < 3) return $sender->sendMessage($this->getUsageMessage('[подробный вопрос к нейросети] §e- не меньше 3 слов.'));
        $message = implode(' ', $args);
        if (!Filter::isAllowed($message)) return $sender->sendMessage($this->lang()::ERR . ' §cНельзя использовать нецензурные выражения!');
        if (!$this->countdown($sender, 60)) return;
        $this->serv()->broadcastMessage('§6[§eChat§fGPT§6] §fВопрос к нейросети от игрока §e' . $sender->getName() . '§f: §7' . $message);
        $result = $this->getChatGptResponse($message . '. В ответе используй максимум 100 символов и русский язык');
        $this->serv()->broadcastMessage('§6[§eChat§fGPT§6] §fОтвет: §7' . str_replace(PHP_EOL, '', $result));
    }

    public function getChatGptResponse($message) {
        $apiKey = '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';
        $url = 'https://api.openai.com/v1/engines/text-davinci-003/completions';
        $headers = array('Content-Type: application/json', 'Authorization: Bearer ' . $apiKey);
        $data = array('prompt' => $message, 'max_tokens' => 100, 'temperature' => 0.5);
    
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        $response = curl_exec($ch);
        curl_close($ch);
    
        $responseData = json_decode($response, true);
        if (isset($responseData['choices'][0]['text'])) {
            return $responseData['choices'][0]['text'];
        }
    
        return null;
    }
}



