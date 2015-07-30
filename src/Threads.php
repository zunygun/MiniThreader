<?php
namespace wapmorgan\MiniThreader;

use \Exception;

class Threads {
    protected $worker;
    protected $number = 0;
    protected $payloads = array();
    protected $threads = array();
    public $storage;

    /**
     * @param callable $worker
     */
    public function __construct($worker) {
        if (!is_callable($worker))
            throw new Exception('Can not use this value as a worker: '.(string)$worker);
        $this->worker = $worker;
    }

    public function setThreadsNumber($number) {
        $number = (int)$number;
        if ($number <= 0)
            throw new Exception('Negative value can\'t be used as threads number');
        $this->number = $number;
    }

    /**
     * @param int $n Ordinal number of thread
     * @param mixed $payload Payload for thread
     */
    public function setPayload($n, $payload) {
        $n = (int)$n;
        if ($n >= $this->number)
            throw new Exception('Thread #'.$n.' is not supposed to be created. Increase number of threads (current value '.$this->number.' or decrease thread number)');
        $this->payloads[$n] = $payload;
    }

    /**
     * @return array Array of Thread's
     */
    public function runThreads() {
        for ($i = 0; $i < $this->number; $i++) {
            $pid = pcntl_fork();
            if ($pid === -1)
                throw new Exception('An unexpected error occured during forking.');
            else if ($pid > 0) {
                // main thread
                $this->threads[] = new Thread($pid);
            } else {
                // new thread
                if (isset($this->payloads[$i]))
                    call_user_func($this->worker, $this, $this->payloads[$i]);
                else
                    call_user_func($this->worker, $this);
                exit();
            }
        }
        return $this->threads;
    }

    public function stillWorkingThread($n) {
        if (!isset($this->threads[$n]))
            return false;
        return $this->threads[$n]->stillWorking();
    }

    public function useCommonStorage($path = null) {
        if ($path === null) {
            if (version_compare(PHP_VERSION, '5.3.6', '<')) {
                $trace = debug_backtrace(false);
                $trace = $trace[0];
            } else if (version_compare(PHP_VERSION, '5.4.0', '<')) {
                $trace = debug_backtrace(0);
                $trace = $trace[0];
            } else {
                $trace = debug_backtrace(0, 1);
                $trace = $trace[0];
            }
            $path = $trace['file'];
        }
        $this->storage = new CommonStorage($path, array(), getmypid());
    }
}
