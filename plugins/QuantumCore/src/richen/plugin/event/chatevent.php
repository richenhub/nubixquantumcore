<?php declare(strict_types=1);

namespace richen\plugin\event;

use richen\engine\entity\player;

class chatevent extends playerevent
{
    protected array $password = [];

    public function onPlayerCreation(\pocketmine\event\player\PlayerCreationEvent $e) {
		$e->setPlayerClass(player::class);

        $e->getPlayerClass()->getData();
	}

    public function onPlayerCommandPreprocess(\pocketmine\event\player\PlayerCommandPreprocessEvent $e) {
        $player = $e->getPlayer();

        if ($player instanceof player) {
            if (!$player->isRegistered()) {
                $e->setCancelled();
                $player->sendMessage('Вы не авторизированны');

                return;
            } else {
                if (!$player->isAuthed()) {
                    $e->setCancelled();
                    $password = $e->getMessage();
                }
            }
        }

        return $player->sendMessage('Вы не авторизированны');
    }
}