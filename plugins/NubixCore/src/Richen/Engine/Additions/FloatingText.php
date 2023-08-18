<?php 

namespace Richen\Engine\Additions;

use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\item\Item;

class FloatingText {
    public static $idfloating;

    public static function removePreCustomFloating(Player $player, $x, $y, $z) {
        if (isset(self::$idfloating[$x . $y . $z])) {
            $id = self::$idfloating[$x . $y . $z];
            self::removeCustomFloating($player, $id);
        }
    }
    
    public static function createCustomFloating(Player $player, $x, $y, $z, $text) {
        if (isset(self::$idfloating[$x . $y . $z])) {
            $id = self::$idfloating[$x . $y . $z];
        } else {
            $id = Entity::$entityCount++;
        }

        $pk = new \pocketmine\network\mcpe\protocol\AddPlayerPacket();
        $pk->eid = $id;
        $pk->uuid = \pocketmine\utils\UUID::fromRandom();
        $pk->username = "null";
        $pk->x = $x + 0.5; $pk->y = $y; $pk->z = $z + 0.5;
        $pk->item = Item::get(Item::AIR);

        $flags = (
            (1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
            (1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
            (1 << Entity::DATA_FLAG_IMMOBILE)
        );

        $pk->metadata = [
            Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
            Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $text],
            Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0],
        ];

        $player->dataPacket($pk);

        self::$idfloating[$x . $y . $z] = $id;
    }

    public static function removeCustomFloating(Player $player, $id) {
        $pk = new \pocketmine\network\mcpe\protocol\RemoveEntityPacket();
        $pk->eid = $id;
        $player->dataPacket($pk);
    }
}