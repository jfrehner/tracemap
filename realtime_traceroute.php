<?php

error_reporting( E_ALL );

$cmd = 'traceroute google.ch';
$outputfile = 'test1.txt';
$pidfile = 'test1.pid';
exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $outputfile, $pidfile));

echo sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $outputfile, $pidfile);
