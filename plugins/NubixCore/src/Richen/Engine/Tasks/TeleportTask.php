<?php 

namespace Richen\Engine\Tasks;

use pocketmine\level\Position;
use pocketmine\scheduler\Task;
use Richen\Custom\NBXPlayer;
use pocketmine\entity\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\math\Vector3;

class TeleportTask extends Task {
    private NBXPlayer $player;
    private Position $position;
    private $countdown = 3;
    private \Richen\Custom\TeleportManager $tpmn;
    private $ticks = 60;
    private $loadMsg;
    private $tpMsg;

    public function __construct(\Richen\Custom\TeleportManager $tpmn, NBXPlayer $player, Position $position, string $loadMsg = '', string $tpMsg = '') {
        $this->player = $player;
        $this->tpmn = $tpmn;
        $this->position = $position;
        $this->countdown = 4;
        $this->ticks = 15;
        $this->loadMsg = $loadMsg === '' ? '§6> §eТелепортация через §b%s' : $loadMsg;
        $this->tpMsg = $tpMsg === '' ? '§eВы телепортировались' : $tpMsg;
    }

    public function onRun($tick) {
        if ($this->countdown > 1 && $this->ticks % 5 === 0) {
            $this->countdown--;
            $this->player->getLevel()->loadChunk($this->position->getX(), $this->position->getZ());
            $pos = $this->position;
            while ($this->player->getLevel()->getBlock($pos)->getId() !== 0 && $pos->getY() < 128) $pos = $pos->add(0, 2, 0);
            $this->position = new Position($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ(), $this->position->getLevel());
        } elseif ($this->countdown === 1) {
            $this->position->add(0.5, 1, 0.5);
            $this->player->teleport($this->position);
            $this->player->sendMessage(sprintf($this->tpMsg, $this->countdown-1));
            $this->player->sendTitle('', $this->tpMsg, 0, 40, 30);
            $this->player->addEffect(Effect::getEffect(Effect::BLINDNESS)->setDuration(40)->setVisible(false));
            $this->countdown--;
            return;
        } elseif ($this->countdown === 0) {
            $this->getHandler()->cancel();
            $this->player->getLevel()->addSound(new \pocketmine\level\sound\EndermanTeleportSound($this->position), [$this->player]);
            $this->tpmn->unsetTeleport();
            return;
        }
        $this->player->sendTitle('', \Richen\Engine\Utils::rainbowify(sprintf($this->loadMsg, $this->countdown), max(0, $this->ticks)), 0, 10, 0);
        $this->ticks--;
    }
}