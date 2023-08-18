<?php

namespace pocketmine\event;


/**
 * Events that can be cancelled must use the interface Cancellable
 */
interface Cancellable {
	public function isCancelled();

	/**
	 * @param bool $forceCancel
	 *
	 * @return mixed
	 */
	public function setCancelled($forceCancel = false);
}