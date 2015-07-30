<?php
namespace wapmorgan\MiniThreader;

use \Exception;

class CommonStorage {
    protected $key;
    protected $memory;
    protected $initialValue;
    protected $semaphore;
    protected $sizeSemaphore;
    protected $mainThreadId;

    /**
     * @param string $path Path of calling script
     * @param mixed $initialValue Default value for storage
     */
    public function __construct($path, $initialValue = 0, $mainThreadId = 0) {
        $alphabet = range('a', 'z');
        $this->key = ftok($path, $alphabet[array_rand($alphabet)]);
        $this->initialValue = $initialValue;
        $this->semaphore = sem_get($this->key);
        $this->sizeSemaphore = sem_get($this->key+1);
        $this->mainThreadId = $mainThreadId;
    }

    public function __destruct() {
        if (getmypid() == $this->mainThreadId)
            shmop_delete($this->memory);
    }

    /**
     * @return mixed Storage data
     */
    public function retrieve() {
        if ($this->memory === null)
            $this->open();
	$result = shmop_read($this->memory, 0, shmop_size($this->memory));
        $data = trim($result);
        if (empty($data))
            $data = $this->initialValue;
        else
            return unserialize($data);
    }

    public function retrieveAndLock() {
        sem_acquire($this->semaphore);
        return $this->retrieve();
    }

    /**
     * @param mixed $value Data to store
     */
    public function store($value) {
        $serialized = serialize($value);
        $size = strlen($serialized);
        if ($this->memory === null)
            $this->open();
        if (shmop_size($this->memory) < $size) {
            $this->expand($size);
        }
        shmop_write($this->memory, $serialized, 0);
    }

    /**
     * @param mixed $value Data to store
     */
    public function storeAndUnlock($value) {
        $this->store($value);
        sem_release($this->semaphore);
    }

    protected function expand($newsize) {
        sem_acquire($this->sizeSemaphore);

        shmop_delete($this->memory);
        $this->memory = shmop_open($this->key, 'c', 0644, $newsize);

        sem_release($this->sizeSemaphore);
    }

    protected function open() {
        // probe
        $level = error_reporting();
        error_reporting(0);
        $this->memory = shmop_open($this->key, 'w', 0, 0);
        error_reporting($level);

        if ($this->memory === false) {
            $this->memory = shmop_open($this->key, 'c', 0666, strlen(serialize($this->initialValue)));
        }
    }
}
