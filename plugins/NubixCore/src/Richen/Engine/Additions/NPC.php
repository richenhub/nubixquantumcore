<?php 

namespace Richen\Engine\Additions;

use pocketmine\entity\Entity;
use pocketmine\entity\LavaSlime;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;

class NPC {
    public static function createInvisibleNPC(Player $player, string $name, array $xyz, int $size = 1) {
        $npc = new CompoundTag('', [
            'Pos' => new ListTag('Pos', [new DoubleTag('', $xyz[0] + 0.5), new DoubleTag('', $xyz[1]), new DoubleTag('', $xyz[2] + 0.5)]),
            'Motion' => new ListTag('Motion', [new DoubleTag('', 0), new DoubleTag('', 0), new DoubleTag('', 0)]),
            'Rotation' => new ListTag('Rotation', [new FloatTag('', $player->getYaw()), new FloatTag('', $player->getPitch())])
        ]);
        $mob = Entity::createEntity(LavaSlime::NETWORK_ID, $player->getLevel(), $npc);
        $mob->setMaxHealth(50);
        $mob->setHealth(50);
		$mob->setNameTagVisible(false);
        $mob->setNameTagAlwaysVisible(false);
		$mob->setDataProperty(Entity::DATA_SCALE, Entity::DATA_TYPE_FLOAT, $size);
	    $mob->setNameTag($name);
        $mob->spawnTo($player);
    }
}