<?php
namespace wapmorgan\MiniThreader;

use \Exception;

class CommonStorage {
    protected $key;
    protected $memory;
    protected $initialValue;

    /**
     * @param string $path Path of calling script
     * @param mixed $initialValue Default value for storage
     */
    public function __construct($path, $initialValue = 0) {
        $alphabet = range('a', 'z');
        $this->key = ftok($path, $alphabet[array_rand($alphabet)]);
        $this->memory = shmop_open($this->key, 'c', 0644, strlen(serialize($initialValue)));
        $this->initialValue = $initialValue;

        $this->store($initialValue);
    }

    public function __destruct() {
        shmop_delete($this->memory);
    }

    /**
     * @return mixed Storage data
     */
    public function retrieve() {
        $data = trim(shmop_read($this->memory, 0, shmop_size($this->memory)));
        if (empty($data))
            $data = $initialValue;
        else
            return unserialize($data);
    }

    /**
     * @param mixed $value Data to store
     */
    public function store($value) {
        $serialized = serialize($value);
        $size = strlen($serialized);
        if (shmop_size($this->memory) < $size) {
            $this->expand($size);
        }
        shmop_write($this->memory, $serialized, 0);
    }

    protected function expand($newsize) {
        $sem = sem_get($this->key);
        sem_acquire($sem);
        shmop_delete($this->memory);
        $this->memory = shmop_open($this->key, 'c', 0644, $newsize);
        sem_release($sem);
    }
}
