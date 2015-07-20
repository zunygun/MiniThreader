<?php
require __DIR__.'/../vendor/autoload.php';

use wapmorgan\MiniThreader\Thread;
use wapmorgan\MiniThreader\Threads;

$client = new Packagist\Api\Client();
$packages = $client->all();
$total = count($packages);

$threads = new Threads(function (Threads $threads, array $payload) use ($client) {
    fwrite(STDOUT, 'My load: '.count($payload).PHP_EOL);
    foreach ($payload as $i => $my_package) {
        try {
            $details = $client->get($my_package);
            $type = $details->getType();
            $data = $threads->storage->retrieve();
            if (isset($data[$type])) $data[$type]++;
            else $data[$type] = 1;
            $threads->storage->store($data);
        } catch (ClientErrorResponseException $e) {
            fwrite(STDOUT, "\n".'Error code: '.$e->getCode().' on package '.$package."\n");
        }
    }
    fwrite(STDOUT, '- Child '.getmypid().' done'.PHP_EOL);
});
$threads->setThreadsNumber(4);
$per_thread = ceil($total / $cores);

for ($i = 0; $i < 4; $i++) {
    $threads->setPayload($i, array_slice($packages, $per_thread * $ordinal, $per_thread, true));
}
$running_threads = $threads->runThreads();

while (count($running_threads) > 0) {
    foreach ($running_threads as $_i => $thread) {
        if (!$thread->stillWorking())
            unset($running_threads[$_i]);
    }
    $sum = array_sum($threads->storage->retrieve());
    $prog = round($sum * 100 / $total);
    echo "\r".$sum.' ('.$prog.'%)';
    sleep(1);
}
