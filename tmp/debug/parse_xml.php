<?php

$file = "update.txt";

$string = file_get_contents($file);
preg_match("/(update time=\")([0-9TZ\-:])+/", $string, $matches);
print_r($matches);

?>