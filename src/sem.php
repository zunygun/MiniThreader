<?php
if (!function_exists('ftok')) {
    function ftok($pathname, $proj_id) {
        $st = @stat($pathname);
        if (!$st) {
            return -1;
        }
        $key = sprintf("%u", (($st['ino'] & 0xffff) | (($st['dev'] & 0xff) << 16) | (($proj_id & 0xff) << 24)));
        return $key;
    }
}

if ( !extension_loaded('sem') ) {
    function sem_get($key) { return fopen(sys_get_temp_dir().'/php.sem.'.$key, 'w+'); }
    function sem_acquire($sem_id) { return flock($sem_id, LOCK_EX); }
    function sem_release($sem_id) { return flock($sem_id, LOCK_UN); }
    function sem_remove($sem_id) { return fclose($sem_id); }
}
