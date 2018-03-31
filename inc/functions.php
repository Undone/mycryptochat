<?php
function getHashForIp()
{
	$address = filter_input(INPUT_SERVER, "REMOTE_ADDR");
	
    return substr(hash("sha256", $address.SEED), 0, 16);
}

function logException($e) {
    file_put_contents(LOGS_FILE_NAME, date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . ' -> ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);
}


function randomString($size) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random = '';
    for ($i = 0; $i < $size; $i++) {
        $random .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $random;
}