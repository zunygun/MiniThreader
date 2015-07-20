<?php
require __DIR__.'/../vendor/autoload.php';

use wapmorgan\MiniThreader\Thread;
use wapmorgan\MiniThreader\Threads;

$threads = new Threads(function (Threads $threads, array $payload) {
    fwrite(STDOUT, 'I am child: '.getmypid().PHP_EOL);
    sleep(rand(1, 3));
    $data = $threads->storage->retrieveAndLock();
    $data[] = getmypid();
    $threads->storage->storeAndUnlock($data);
    fwrite(STDOUT, '- Child '.getmypid().' done'.PHP_EOL);
});
$threads->setThreadsNumber(4);

$running_threads = $threads->runThreads();

while (count($running_threads) > 0) {
    foreach ($running_threads as $_i => $thread) {
        if (!$thread->stillWorking())
            unset($running_threads[$_i]);
    }
    sleep(1);
}
echo 'All children done'.PHP_EOL;
