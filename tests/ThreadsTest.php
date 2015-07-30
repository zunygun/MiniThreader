<?php
use wapmorgan\MiniThreader\Threads;

class ThreadsTest extends PHPUnit_Framework_TestCase {
    public function testSimple() {
        $threads = new Threads(function (Threads $threads) {
            $data = $threads->storage->retrieveAndLock();
            $data[] = getmypid();
            $threads->storage->storeAndUnlock($data);
        });
        $threads->useCommonStorage();
        $threads->storage->store(array());
        $threads->setThreadsNumber(4);
        $children = $threads->runThreads();

        $running = $children;
        while (count($running) > 0) {
            foreach ($running as $i => $thread) {
                if (!$thread->stillWorking())
                    unset($running[$i]);
            }
            usleep(200);
        }
        $result = $threads->storage->retrieve();

        $pids = array();
        foreach ($children as $child) {
            $this->assertContains($child->getPid(), $result);
        }
    }

}
