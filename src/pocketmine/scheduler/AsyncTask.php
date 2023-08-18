<?php

namespace pocketmine\scheduler;

use pocketmine\Server;
use pocketmine\utils\MainLogger;

abstract class AsyncTask extends \Threaded implements \Collectable{
	/**
	 * @var \SplObjectStorage|null
	 * Used to store objects on the main thread which should not be serialized.
	 */
	private static $localObjectStorage;

	/** @var AsyncWorker $worker */
	public $worker = null;

	/** @var \Threaded */
	public $progressUpdates;

	private $result = null;
	private $serialized = false;
	private $cancelRun = false;
	/** @var int */
	private $taskId = null;

	private $crashed = false;

	private $isGarbage = false;

	private $isFinished = false;

	/**
	 * @return bool
	 */
	public function isGarbage() : bool{
		return $this->isGarbage;
	}

	public function setGarbage(){
		$this->isGarbage = true;
	}

	/**
	 * @return bool
	 */
	public function isFinished() : bool{
		return $this->isFinished;
	}

	public function run(){
		$this->result = null;
		$this->isGarbage = false;

		if($this->cancelRun !== true){
			try{
				$this->onRun();
			}catch(\Throwable $e){
				$this->crashed = true;
				$this->worker->handleException($e);
			}
		}

		$this->isFinished = true;
		//$this->setGarbage();
	}

	/**
	 * @return bool
	 */
	public function isCrashed(){
		return $this->crashed or $this->isTerminated();
	}

	/**
	 * @return mixed
	 */
	public function getResult(){
		return $this->serialized ? unserialize($this->result) : $this->result;
	}

	public function cancelRun(){
		$this->cancelRun = true;
	}

	/**
	 * @return bool
	 */
	public function hasCancelledRun(){
		return $this->cancelRun === true;
	}

	/**
	 * @return bool
	 */
	public function hasResult(){
		return $this->result !== null;
	}

	/**
	 * @param mixed $result
	 */
    public function setResult($result){
        $this->result = ($this->serialized = !is_scalar($result)) ? serialize($result) : $result;
	}

	/**
	 * @param $taskId
	 */
	public function setTaskId($taskId){
		$this->taskId = $taskId;
	}

	/**
	 * @return int
	 */
	public function getTaskId(){
		return $this->taskId;
	}

	/**
	 * @see AsyncWorker::getFromThreadStore()
	 *
	 * @param string $identifier
	 *
	 * @return mixed
	 */
	public function getFromThreadStore($identifier){
		if($this->worker === null or $this->isGarbage()){
			throw new \BadMethodCallException("Objects stored in AsyncWorker thread-local storage can only be retrieved during task execution");
		}
		return $this->worker->getFromThreadStore($identifier);
	}

	/**
	 * @see AsyncWorker::saveToThreadStore()
	 *
	 * @param string $identifier
	 * @param mixed  $value
	 */
	public function saveToThreadStore($identifier, $value){
		if($this->worker === null or $this->isGarbage()){
			throw new \BadMethodCallException("Objects can only be added to AsyncWorker thread-local storage during task execution");
		}
		$this->worker->saveToThreadStore($identifier, $value);
	}

	/**
	 * @see AsyncWorker::removeFromThreadStore()
	 *
	 * @param string $identifier
	 */
	public function removeFromThreadStore(string $identifier) : void{
		if($this->worker === null or $this->isGarbage()){
			throw new \BadMethodCallException("Objects can only be removed from AsyncWorker thread-local storage during task execution");
		}
		$this->worker->removeFromThreadStore($identifier);
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	public abstract function onRun();

	/**
	 * Actions to execute when completed (on main thread)
	 * Implement this if you want to handle the data in your AsyncTask after it has been processed
	 *
	 * @param Server $server
	 *
	 * @return void
	 */
	public function onCompletion(Server $server){

	}

	/**
	 * Call this method from {@link AsyncTask#onRun} (AsyncTask execution therad) to schedule a call to
	 * {@link AsyncTask#onProgressUpdate} from the main thread with the given progress parameter.
	 *
	 * @param \Threaded|mixed $progress A Threaded object, or a value that can be safely serialize()'ed.
	 */
	public function publishProgress($progress){
		$this->progressUpdates[] = serialize($progress);
	}

	/**
	 * @internal Only call from AsyncPool.php on the main thread
	 *
	 * @param Server $server
	 */
	public function checkProgressUpdates(Server $server){
		while($this->progressUpdates->count() !== 0){
			$progress = $this->progressUpdates->shift();
			$this->onProgressUpdate($server, unserialize($progress));
		}
	}

	/**
	 * Called from the main thread after {@link AsyncTask#publishProgress} is called.
	 * All {@link AsyncTask#publishProgress} calls should result in {@link AsyncTask#onProgressUpdate} calls before
	 * {@link AsyncTask#onCompletion} is called.
	 *
	 * @param Server          $server
	 * @param \Threaded|mixed $progress The parameter passed to {@link AsyncTask#publishProgress}. If it is not a
	 *                                  Threaded object, it would be serialize()'ed and later unserialize()'ed, as if it
	 *                                  has been cloned.
	 */
	public function onProgressUpdate(Server $server, $progress){

	}

	protected function storeLocal($complexData){
		if($this->worker !== null and $this->worker === \Thread::getCurrentThread()){
			throw new \BadMethodCallException("Objects can only be stored from the parent thread");
		}

		if(self::$localObjectStorage === null){
			self::$localObjectStorage = new \SplObjectStorage(); //lazy init
		}

		if(isset(self::$localObjectStorage[$this])){
			throw new \InvalidStateException("Already storing complex data for this async task");
		}
		self::$localObjectStorage[$this] = $complexData;
	}

	protected function fetchLocal(){
		try{
			return $this->peekLocal();
		}finally{
			if(self::$localObjectStorage !== null){
				unset(self::$localObjectStorage[$this]);
			}
		}
	}

	protected function peekLocal(){
		if($this->worker !== null and $this->worker === \Thread::getCurrentThread()){
			throw new \BadMethodCallException("Objects can only be retrieved from the parent thread");
		}

		if(self::$localObjectStorage === null or !isset(self::$localObjectStorage[$this])){
			throw new \InvalidStateException("No complex data stored for this async task");
		}

		return self::$localObjectStorage[$this];
	}

	/**
	 * @internal Called by the AsyncPool to destroy any leftover stored objects that this task failed to retrieve.
	 * @return bool
	 */
	public function removeDanglingStoredObjects() : bool{
		if(self::$localObjectStorage !== null and isset(self::$localObjectStorage[$this])){
			unset(self::$localObjectStorage[$this]);
			return true;
		}

		return false;
	}
}