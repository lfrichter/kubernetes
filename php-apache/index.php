<?php

$x = 0.000001;
for ($i = 0; $i <= 100000000; ++$i) {
    $x += sqrt($x);
}
echo  PHP_EOL.'Code.education Rocks! '.PHP_EOL;
