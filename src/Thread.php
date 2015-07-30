<?php
namespace wapmorgan\MiniThreader;

use \Exception;

class Thread {
    protected $pid;

    public function __construct($pid) {
        $this->pid = $pid;
    }

    public function getPid() {
        return $this->pid;
    }

    public function stillWorking() {
        return pcntl_waitpid($this->pid, $status, WNOHANG) === 0;
    }

    public function getExitStatus() {
        if (pcntl_waitpid($this->pid, $status, WNOHANG) <= 0)
            return false;
        return pcntl_wexitstatus($status);
    }
}
