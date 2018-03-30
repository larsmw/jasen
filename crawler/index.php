<?php

error_reporting(E_ALL);
ini_set('display_errors', TRUE);

// This should be made to a cron daemon

$pid = pcntl_fork(); // fork
if ($pid < 0)
    exit;
else if ($pid) // parent
    exit;
else { // child
    $sid = posix_setsid();

    if ($sid < 0)
        exit;

    for ($i = 0; $i <= 6; $i++) { // do something for 5 minutes
        sleep(5);
        echo "pid : $pid\n";
        echo "sid : $sid\n";
        echo "$i...\n";
    }
    echo "$sid : done\n";
}

