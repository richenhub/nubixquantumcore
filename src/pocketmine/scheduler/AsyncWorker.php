<?php

namespace pocketmine\scheduler;

use pocketmine\utils\MainLogger;
use pocketmine\utils\Utils;
use pocketmine\Worker;
use function error_reporting;
use function gc_enable;
use function ini_set;
use function set_error_handler;

class AsyncWorker extends Worker{
	/** @var mixed[] */
	private static $store = [];

	private $logger;
	private $id;

	/** @var int */
	private $memoryLimit;

	public function __construct(\ThreadedLogger $logger, int $id, int $memoryLimit){
		$this->logger = $logger;
		$this->id = $id;
		$this->memoryLimit = $memoryLimit;
	}

	public function run(){
		error_reporting(-1);

		$this->registerClassLoader();

		//set this after the autoloader is registered
		set_error_handler([Utils::class, 'errorExceptionHandler']);

		if($this->logger instanceof MainLogger){
			$this->logger->registerStatic();
		}

		gc_enable();

		if($this->memoryLimit > 0){
			ini_set('memory_limit', $this->memoryLimit . 'M');
			$this->logger->debug("Set memory limit to " . $this->memoryLimit . " MB");
		}else{
			ini_set('memory_limit', '-1');
			$this->logger->debug("No memory limit set");
		}
	}

	public function getLogger() : \ThreadedLogger{
		return $this->logger;
	}

	/**
	 * @param \Throwable $e
	 */
	public function handleException(\Throwable $e){
		$this->logger->logException($e);
	}

	public function getAsyncWorkerId() : int{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getThreadName(){
		return "Asynchronous Worker #" . $this->id;
	}

	/**
	 * Saves mixed data into the worker's thread-local object store. This can be used to store objects which you
	 * want to use on this worker thread from multiple AsyncTasks.
	 *
	 * @param string $identifier
	 * @param mixed  $value
	 */
	public function saveToThreadStore(string $identifier, $value) : void{
		self::$store[$identifier] = $value;
	}
	
	/**
	 * Retrieves mixed data from the worker's thread-local object store.
	 *
	 * Note that the thread-local object store could be cleared and your data might not exist, so your code should
	 * account for the possibility that what you're trying to retrieve might not exist.
	 *
	 * Objects stored in this storage may ONLY be retrieved while the task is running.
	 *
	 * @param string $identifier
	 *
	 * @return mixed
	 */
	public function getFromThreadStore(string $identifier){
		return self::$store[$identifier] ?? null;
	}

	/**
	 * Removes previously-stored mixed data from the worker's thread-local object store.
	 *
	 * @param string $identifier
	 */
	public function removeFromThreadStore(string $identifier) : void{
		unset(self::$store[$identifier]);
	}
}