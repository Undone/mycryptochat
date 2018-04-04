<?php
function logException($e)
{
    file_put_contents(LOGS_FILE_NAME, date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . ' -> ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);
}

function randomString($size)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random = '';
    for ($i = 0; $i < $size; $i++) {
        $random .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $random;
}

function generateRandomHash()
{
	return hash("sha256", time().SEED.rand(1,100));
}