<?php declare(strict_types=1);

namespace richen\events;

class event extends \richen\managers\manager implements \pocketmine\event\Listener
{
    public function onPlayerCreation(\pocketmine\event\player\PlayerCreationEvent $ev)
    {
		$ev->setPlayerClass(\richen\custom\customplayer::class);
	}

    public function onJoin(\pocketmine\event\player\PlayerJoinEvent $ev)
    {
        $player = $ev->getPlayer();

        if (!$this->easyauth()->isRegistered($player)) {
            

            return;
        }

        if (!$this->easyauth()->isAuth($player)) {

        }
    }

}