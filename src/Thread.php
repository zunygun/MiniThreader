<?php
namespace wapmorgan\MiniThreader;

use \Exception;

class Thread {
    protected $pid;

    public function __construct($pid) {
        $this->pid = $pid;
    }

    public function stillWorking() {
        return pcntl_waitpid($this->pid, WNOHANG) === 0;
    }

    public function getExitStatus() {
        $status = pcntl_waitpid($this->pid, WNOHANG);
        if ($status <= 0)
            return false;
        return pcntl_wexitstatus($status);
    }
}
