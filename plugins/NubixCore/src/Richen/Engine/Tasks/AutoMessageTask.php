<?php 

namespace Richen\Engine\Tasks;

use Richen\Engine\Additions\BossBar;
use skymin\bossbar\BossBarAPI;

class AutoMessageTask extends TaskManager {
    private $server;

    private $messages = [
        "§d✦ §eСледите за новостями обновлений сервера в группе §bВК §e- §avk.com/nubix",
        "§d✦ §eХочешь быть §bсамым крутым§e, иметь много команд? Купи донат: §anubix.ru",
        "§d✦ §eВ группе §bVK §eпроходят часто конкурсы §aна донат§e, успей! §bvk.com/nubix",
        "§d✦ §eПокупая привилегии на сайте §bnubix.ru§e, вы помогаете развивать сервер.",
        "§d✦ §e§eПВП §b/warp pvp, §eМагазин §a/warp shop",
        "§d✦ §fА вы хотите испытать §aудачу§f? Попробуйте купить себе §eДонат-Кейс §fна сайте\n§a☻ §fИ выбить из него крутую привилегию: §f/dc - инфо"
    ];

    private $donateMessages = [
        "§d✦ §eСледите за новостями обновлений сервера в группе §bВК §e- §avk.com/nubix",
        "§d✦ §eХочешь быть §bсамым крутым§e, иметь много команд? Купи донат: §anubix.ru",
        "§d✦ §eВ группе §bVK §eпроходят часто конкурсы §aна донат§e, успей! §bvk.com/nubix",
        "§d✦ §eПокупая привилегии на сайте §bnubix.ru§e, вы помогаете развивать сервер.",
        "§d✦ §e§eПВП §b/warp pvp, §eМагазин §a/warp shop",
        "§d✦ §fА вы хотите испытать §aудачу§f? Попробуйте купить себе §eДонат-Кейс §fна сайте\n§a☻ §fИ выбить из него крутую привилегию: §f/dc - инфо"
    ];

    private $index1 = 0;
    private $index2 = 0;

    public function onRun($tick): void {
        if ($this->index1 >= count($this->messages)) $this->index1 = 0;
        if ($this->index2 >= count($this->donateMessages)) $this->index2 = 0;

        $this->core()->serv()->getLogger()->info($this->messages[$this->index1]);
        $this->core()->serv()->getLogger()->info($this->donateMessages[$this->index2]);

        foreach ($this->core()->serv()->getOnlinePlayers() as $player) {
            if ($player->hasPermission('noads')) {
                //$player->sendMessage(PHP_EOL . $this->donateMessages[$this->index2]);
            } else {
                //$player->sendMessage(PHP_EOL . $this->messages[$this->index1]);
            }
            BossBar::getInstance()->setTitle($this->messages[$this->index1], 0);
            BossBar::getInstance()->setPercentage((int)((100 / count($this->messages)) * $this->index1+1), 0);
            //echo (int)((100 / count($this->messages)) * $this->index1+1);
        }

        $this->index1++;
        $this->index2++;
    }
}