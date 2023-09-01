<?php declare(strict_types=1);

namespace richen\plugin\event;
use richen\engine\entity\player;

class joinevent extends playerevent
{
    public function onPlayerCreation(\pocketmine\event\player\PlayerCreationEvent $e) {
		$e->setPlayerClass(player::class);
	}

    public function onPlayerJoin(\pocketmine\event\player\PlayerJoinEvent $e) {
        $e->setJoinMessage(null);
        $player = $e->getPlayer();
        $player->sendMessage('Вы зашли на сервер');

        if ($player instanceof player) {
            if ($player->isRegistered()) {
                $player->sendMessage('Вы уже зарегистрированы');
            } else {
                $player->sendMessage('Вы не зарегистрированы');
            }
        }

    }

    public function onPlayerQuit(\pocketmine\event\player\PlayerQuitEvent $e) {
        $e->setQuitMessage(null);
        $player = $e->getPlayer();
    }

    public function onPlayerLogin(\pocketmine\event\player\PlayerLoginEvent $e) {
        $player = $e->getPlayer();
    }
}