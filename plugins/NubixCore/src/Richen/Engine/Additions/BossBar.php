<?php 

namespace Richen\Engine\Additions;

use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\SetEntityDataPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\Server;
use pocketmine\network\mcpe\protocol\MoveEntityPacket;
use pocketmine\Player;

use pocketmine\entity\Attribute;

class BossBarValues extends Attribute {
	public $min, $max, $value, $name;
	public function __construct($min, $max, $value, $name){
		$this->min = $min;
		$this->max = $max;
		$this->value = $value;
		$this->name = $name;
	}
	public function getMinValue(): float { return $this->min; }
	public function getMaxValue(): float { return $this->max; }
	public function getValue(): float { return $this->value; }
	public function getName(): string { return $this->name; }
	public function getDefaultValue(): float { return $this->min; }
}

class BossBar {
	public array $eids;
	public static $instance;
	private array $messages;
	public function __construct() {
		self::$instance = $this;

		$this->messages = [
			[
				'§l§eНаша группа ВКонтакте: §bvk.com/nubix',
				'§l§6Купить донат §7- §enubix.ru'
			], [
				'§l§6Помощь по привату §7- §e/rg help',
				'§l§6Купить донат §7- §enubix.ru'
			], [
				'§l§eСлучайная телепортация: §d/rtp',
				'§l§6Купить донат §7- §enubix.ru'
			],
		];
	}
	public static function getInstance() {
		return self::$instance;
	}

	public function setEid($key, $value) {
		$this->eids[mb_strtolower($key)] = $value;
	}
	public function remEid($key) {
		$this->removeBossBar($key);
		unset($this->eids[mb_strtolower($key)]);
	}
	public function getEid($key) {
		if (!isset($this->eids[mb_strtolower($key)])) {
			$this->eids[mb_strtolower($key)] = mt_rand(1, PHP_INT_MAX);
		}
		return $this->eids[mb_strtolower($key)];
	}

	public function getMessages() {
		return $this->messages;
	}
	
	public function sendBossBarToPlayer(Player $player, int $eid, string $title = '§enubix.ru') {
		$packet = new AddEntityPacket();
		$packet->eid = $eid;
		$packet->type = 52;
		$packet->yaw = 0;
		$packet->pitch = 0;
		$packet->metadata = [
			Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
			Entity::DATA_FLAGS => [
				   Entity::DATA_TYPE_LONG, 0 ^ 1 
				<< Entity::DATA_FLAG_SILENT ^ 1 
				<< Entity::DATA_FLAG_INVISIBLE ^ 1 
				<< Entity::DATA_FLAG_NO_AI
			],
			Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0], 
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title],
			Entity::DATA_BOUNDING_BOX_WIDTH => [Entity::DATA_TYPE_FLOAT, 0],
			Entity::DATA_BOUNDING_BOX_HEIGHT => [Entity::DATA_TYPE_FLOAT, 0]];
		$packet->x = $player->x;
		$packet->y = $player->y - 28;
		$packet->z = $player->z;
		$player->dataPacket($packet);
		
		$bpk = new BossEventPacket();
		$bpk->eid = $eid;
		$player->dataPacket($bpk);
	}

	public static function setPercentage(int $percentage, int $eid){
		if(!count(Server::getInstance()->getOnlinePlayers()) > 0) return;
		
		$upk = new UpdateAttributesPacket();
		$upk->entries[] = new BossBarValues(0, 300, max(0.5, min([$percentage, 100])) / 100 * 300, 'minecraft:health');
		$upk->entityId = $eid;
		Server::getInstance()->broadcastPacket(Server::getInstance()->getOnlinePlayers(), $upk);
		
		$bpk = new BossEventPacket();
		$bpk->eid = $eid;
		Server::getInstance()->broadcastPacket(Server::getInstance()->getOnlinePlayers(), $bpk);
	}

	public static function setTitle(string $title, int $eid) {		
		$npk = new SetEntityDataPacket();
		$npk->metadata = [Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title]];
		$npk->eid = $eid;
		Server::getInstance()->broadcastPacket(Server::getInstance()->getOnlinePlayers(), $npk);
		
		$bpk = new BossEventPacket();
		$bpk->eid = $eid;
		Server::getInstance()->broadcastPacket(Server::getInstance()->getOnlinePlayers(), $bpk);
	}

	public static function removeBossBar(int $eid) {
		$pk = new RemoveEntityPacket();
		$pk->eid = $eid;
		Server::getInstance()->broadcastPacket(Server::getInstance()->getOnlinePlayers(), $pk);
	}

	public static function playerMove(\pocketmine\level\Location $pos, $eid){
		$pk = new MoveEntityPacket();
		$pk->x = $pos->x;
		$pk->y = $pos->y - 28;
		$pk->z = $pos->z;
		$pk->eid = $eid;
		$pk->yaw = $pk->pitch = $pk->headYaw = 0;
		return clone $pk;
	}
}