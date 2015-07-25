<?php
namespace wapmorgan\MiniThreader;

use \Exception;

class CommonStorage {
    protected $key;
    protected $memory;
    protected $initialValue;
    protected $semaphore;

    /**
     * @param string $path Path of calling script
     * @param mixed $initialValue Default value for storage
     */
    public function __construct($path, $initialValue = 0) {
        $alphabet = range('a', 'z');
        $this->key = ftok($path, $alphabet[array_rand($alphabet)]);
        $this->initialValue = $initialValue;
        $this->semaphore = sem_get($this->key);
    }

    public function __destruct() {
        shmop_delete($this->memory);
    }

    /**
     * @return mixed Storage data
     */
    public function retrieve() {
	echo getmypid().'Retrieved'.PHP_EOL;
        if ($this->memory === null)
            $this->open();
	$result = shmop_read($this->memory, 0, shmop_size($this->memory));
	var_dump($result);
        $data = trim($result);
        if (empty($data))
            $data = $this->initialValue;
        else
            return unserialize($data);
    }

    public function retrieveAndLock() {
	echo getmypid().'Locked'.PHP_EOL;
        sem_acquire($this->semaphore);
        return $this->retrieve();
    }

    /**
     * @param mixed $value Data to store
     */
    public function store($value) {
	echo getmypid().'Stored'.PHP_EOL;
	var_dump($value);
        $serialized = serialize($value);
        $size = strlen($serialized);
        if ($this->memory === null)
            $this->open();
        if (shmop_size($this->memory) < $size) {
	    echo 'Expanding size from '.shmop_size($this->memory).' to '.$size.PHP_EOL;
            $this->expand($size);
	    echo 'New size is '.shmop_size($this->memory).PHP_EOL;
        }
        shmop_write($this->memory, $serialized, 0);
    }

    /**
     * @param mixed $value Data to store
     */
    public function storeAndUnlock($value) {
        $this->store($value);
        sem_release($this->semaphore);
	echo getmypid().'Unlocked'.PHP_EOL;
    }

    protected function expand($newsize) {
//        sem_acquire($this->semaphore);

        shmop_delete($this->memory);
        $this->memory = shmop_open($this->key, 'c', 0644, $newsize);

//        sem_release($this->semaphore);
    }

    protected function open() {
        echo getmypid().'Opened'.PHP_EOL;
        // probe
        $this->memory = @shmop_open($this->key, 'w', 0, 0);
        if ($this->memory === false) {
            $this->memory = shmop_open($this->key, 'c', 0644, strlen(serialize($this->initialValue)));
        }
    }
}
