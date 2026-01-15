<?php
file_put_contents('/tmp/php-environ.log', print_r($_SERVER, true)."\n", FILE_APPEND);
echo 'ok';