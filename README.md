# MiniThreader
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/wapmorgan/MiniThreader/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/wapmorgan/MiniThreader/?branch=master)

Simple calculations-oriented threading library for PHP

# Structure
![](Multithreading.png)

# Goals
This multithreading library aims two goals:

1. Be as simple as possible.
2. Let user of library parallel some mathematical or other calculations.

# Workflow
1. Create `Threads` object (wapmorgan\MiniThreader\Threads).
  ``` php
  $threads = new Threads(function (Threads $threads, $payload) {
    // do some work here
    sleep(rand(2, 5));
    echo 'Child '.getmypid().' done'.PHP_EOL;
  });
  ```
  
2. Specify number of threads.
  ``` php
  $threads->setThreadsNumber(4);
  ```
  
3. Set the payload for all threads.
  ``` php
  $threads->setPayload(0, 'payload');
  $threads->setPayload(1, 123);
  $threads->setPayload(2, array('payload'));
  $threads->setPayload(3, new stdclass);
  ```
  
4. Run all threads.
  ``` php
  $children = $threads->runThreads();
  ```
  
5. Wait for all threads to finish.
  ``` php
  while (count($children) > 0) {
    foreach ($children as $_i => $thread) {
      if (!$thread->stillWorking())
          unset($children[$_i]);
    }
    sleep(1);
  }
  ```
  
# Common storage
If you want to let threads have common variables that any thread can edit and save, call `useCommonStorage()` before starting all threads.
``` php
$threads->useCommonStorage();
```
And in code of threads use `$storage` property.
``` php
function (Threads $threads, $payload) {
  $data = $threads->storage->retrieveAndLock();
  $data += 1;
  $threads->storage->storeAndUnlock($data);
}
```
After finishing all threads, grab result.
``` php
$data = $threads->storage->retrieve();
```
Also, there's non-blocking analogs: `retrieve()` and `store()`. They're faster cause of not using semaphores, but are not very reliable (data corruption is possible if two threads will try to write at the same time).
