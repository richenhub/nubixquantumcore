<?php 

namespace Richen\Engine\Tasks;

class ClearLaggTask extends TaskManager {
    public function onRun($tick): void {
        $levels = $this->core()->serv()->getLevels();
        $removeMessage = '§8[§c☣§8] §6Весь дроп и мусор с земли - §cбыли удалены';
        foreach ($levels as $level) {
            $players = $level->getPlayers();
            foreach ($level->getEntities() as $entity) {
                if (!$entity instanceof \pocketmine\entity\Creature) {
                    $entity->close();
                }
                elseif (!$entity instanceof \pocketmine\entity\Human) {
                    switch ($entity->getNameTag()) {
                        case '       §d§lＡＵＣＴＩＯＮ§r' . "\n" . '§fНажми на NPC для просмотра!':
                        case "         §l§6КУЗНЕЦ"."\n"."§7▶ §eпомогаю в §bкрафтах §7◀":
                        case "      §6❃ §cКве§4сты §6❃\n§7Нажмите что-бы открыть!":
                        case "§a§l         Фермер\n§f§lНажмите чтобы открыть":
                        case "      §l§eСкупщик\n§r§fНажмите чтобы открыть":
                            $close = false;
                            break;
                    }
                    if (!isset($close)) {
                        $entity->close();
                    }
                }
            }
            if ($level->getName() === 'world') {
                $this->core()->serv()->broadcastPopup($removeMessage, $players);
            }
        }
        $this->core()->serv()->getLogger()->info($removeMessage);
    }
}