<?php 

namespace Richen\Commands;

class auc extends \Richen\NubixCmds {

    public function execute(\pocketmine\Command\CommandSender $sender, $label, array $args) {
        if (!$this->checkPermission($sender)) return;
        if (!$sender instanceof \pocketmine\Player) return $sender->sendMessage($this->getConsoleUsage());
		if (!$sender->isSurvival()) return $sender->sendMessage("§a➛ §cАукцион доступен только в §a§lВыживании!§r");

        $auction = \Richen\Engine\Auction\Auction::getInstance();

		$sendHelpMessage = function() use ($sender, $label) : void{
			$sender->sendMessage("§a➛ §fОткрыть аукцион §7§l/auc§r");
			$sender->sendMessage("§a➛ §fПродать предмет §7§l/auc sell§r");
			$sender->sendMessage("§a➛ §fВернуть непроданные предметы §7§l/auc back§r");
		};
		if(!isset($args[0])){
			$auction->open($sender);
			$sendHelpMessage();
			return true;
		}
		$argument = strtolower(array_shift($args));
		if($argument === 'open'){
			$auction->open($sender);
		}elseif($argument === 'back'){
			if(!$auction->hasInvalidatedItems($sender)){
				$sender->sendMessage("§a➛ §fТы ничего не продаёшь или твои предметы §bещё не были сняты §bс аукциона!");
				return true;
			}
			$d = (array)$auction->invalidated_items->get($senderName = $sender->getLowerCaseName(), []);
			$items = array_map(function(string $serialized): \pocketmine\item\Item {
				return \Richen\Engine\Auction\Helper::deserializeItem($serialized);
			}, $d);
			foreach($items as $item){
				if (!$sender->getInventory()->canAddItem($item)) {
					$sender->sendMessage("§a➛ §cОсвободи больше места в инвентаре!");
					return true;
				}
			}
			$sender->getInventory()->addItem(...$items);
			$auction->invalidated_items->remove($senderName);
			$auction->invalidated_items->save();
			$sender->sendMessage("§a➛ §fВсе непроданные предметы успешно §dвозвращены!");
		}elseif($argument === 'sell'){
			if ($sender->getGamemode() !== 0) {
				$sender->sendMessage("§a➛ §fВещи можно продавать только из режима выживания!");
				return true;
			}
			if($auction->slotsLimited($sender)){
				$sender->sendMessage("§a➛ §fЧтобы продавать §6§lбольше §rпредметов, покупай привилегию на сайте §a" . $auction::AUTODONATE_SITE);
				return true;
			}
			if($auction->noFreeSpace()){
				$sender->sendMessage("§a➛ §fНа аукционе сейчас продаётся §cслишком много §fпредметов!");
				return true;
			}
			if(!isset($args[0])){
				$sender->sendMessage("§a➛ §fФормат использования: §7§l/" . $label . " sell §f(цена)");
				return true;
			}
			$item = $sender->getInventory()->getItemInHand();
			if($item->getId() === 0){
				$sender->sendMessage("§a➛ §fВозьми §6в руку §fпредмет, чтобы выставить его на продажу!");
				return true;
			}
			if(in_array($item->getId(), $auction::RESTRICTED_ITEM_IDS, true)){
				$sender->sendMessage("§a➛ §fЭтот предмет §cзапрещено §fвыставлять на §bАукцион!");
				return true;
			}
			if($item->hasCompoundTag() and isset($item->getNamedTag()->auctionWindowItem)){
				$sender->getInventory()->setItemInHand(new \pocketmine\item\Item(0));
				$sender->sendMessage("§a➛ §fЭтот предмет §cзапрещено §fвыставлять на §bАукцион!");
				return true;
			}
			$price = (int)array_shift($args);
			$monetaryUnit = '$';
			if($price < max(0, $auction::MIN_PRICE)){
				$prettyMinPrice = \Richen\Engine\Auction\Helper::toPrettyNumber(number_format(max(0, $auction::MIN_PRICE)));
				$sender->sendMessage("§a➛ §cМинимальная цена: §6" . $prettyMinPrice . " " . $monetaryUnit);
				return true;
			}
			if($price > $auction::MAX_PRICE){
				$prettyMaxPrice = \Richen\Engine\Auction\Helper::toPrettyNumber(number_format($auction::MAX_PRICE));
				$sender->sendMessage("§a➛ §cМаксимальная цена за предмет: §6" . $prettyMaxPrice . " " . $monetaryUnit);
				return true;
			}
			$auction->upForAuction($sender, $item, $price);
		}
		return true;
    }
}