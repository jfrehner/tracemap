<?php
$handle = popen("traceroute google.ch 2>&1", "r");
while(!feof($handle)) {
    $buffer = fgets($handle);
    $buffer = "<p>".$buffer."</p>\n";
    echo $buffer;
    $myfile = file_put_contents('logs.txt', $buffer.PHP_EOL , FILE_APPEND);
}
pclose($handle);
