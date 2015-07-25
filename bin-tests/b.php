<?php
require __DIR__.'/../vendor/autoload.php';

use wapmorgan\MiniThreader\Thread;
use wapmorgan\MiniThreader\Threads;

$threads = new Threads(function (Threads $threads, $payload) {
    fwrite(STDOUT, 'I am child: '.getmypid().PHP_EOL);
    sleep(getmypid() % 4);
    $data = $threads->storage->retrieveAndLock();
    $data[] = getmypid();
    $threads->storage->storeAndUnlock($data);
    fwrite(STDOUT, '- Child '.getmypid().' done'.PHP_EOL);
});
$threads->useCommonStorage();
$threads->setThreadsNumber(4);
$threads->setPayload(0, 1);
$threads->setPayload(1, 1);
$threads->setPayload(2, 1);
$threads->setPayload(3, 1);

$running_threads = $threads->runThreads();

while (count($running_threads) > 0) {
    foreach ($running_threads as $_i => $thread) {
        if (!$thread->stillWorking())
            unset($running_threads[$_i]);
    }
    echo 'Total children still running: '.count($running_threads).PHP_EOL;
    sleep(1);
}
var_dump($threads->storage->retrieve());
echo 'All children done'.PHP_EOL;
