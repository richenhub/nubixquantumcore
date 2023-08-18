<?php 

namespace Richen\Engine\Additions;

class Stats extends \Richen\Engine\Manager {

    const PLACE = 0;
    const BREAK = 1;
    const MESSAGES = 2;
    const KILLS = 3;
    const DEATH = 5;

    protected array $fields = [
        self::PLACE => 'place',
        self::BREAK => 'break',
        self::KILLS => 'kills',
        self::DEATH => 'death',
        self::MESSAGES => 'messages'
    ];

    public static Stats $stats; public function __construct() { self::$stats = $this; }

    public static function stats() {
        return self::$stats;
    }

    public function getTop($type, $limit = 10): array {
        $fields = $this->fields;
        if (!isset($fields[$type])) {
            throw new \InvalidArgumentException('Такого ' . $type . ' поля несуществует');
        }

        if ($type === self::KILLS) {
            $query = "SELECT user_id, kills, death, (kills / NULLIF(death, 0)) as kd FROM stats WHERE death > 0 ORDER BY kd DESC LIMIT $limit";
            $query = "SELECT user_id, kills, death, (kills / NULLIF(death, 0)) as kd FROM stats WHERE death > 0 ORDER BY kills DESC LIMIT $limit";
        } else {
            $field = $fields[$type];
            $query = "SELECT user_id, $field FROM stats ORDER BY $field DESC LIMIT $limit";
        }

        $result = $this->core()->data()->conn()->query($query);

        $top = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $top[] = $row;
        }

        return $top;
    }

    public function spawnTopsForPlayer(\pocketmine\Player $player) {
        $topkd = [138, 70, 241];
        $kd = $this->getTop(SELF::KILLS);
        $y = 0;
        $i = 1;
        foreach ($kd as $data) {
            FloatingText::createCustomFloating($player, $topkd[0], $topkd[1] - $y, $topkd[2], '§d' . $i . ') §e' . $this->core()->user()->getUserPropById($data['user_id'], 'username') . ' §7- §6§l' . $data['kills'] . ' §r§fубийств §7/ §f§lK/D§r §7- §e' . $data['kd']);
            $y += 0.3;
            $i++;
        }
    }

    public function despawnTopsForPlayer(\pocketmine\Player $player) {
        $topkd = [138, 67, 241];
        $y = 0;
        for ($x = 0; $x < 10; $x++) {
            FloatingText::removePreCustomFloating($player, $topkd[0], $topkd[1] - $y, $topkd[2]);
            $y += 0.3;
        }
    }
}