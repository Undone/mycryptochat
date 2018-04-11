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

// Generate a random sha256 hash, this will be used to create a temporary session token for a user
function generateRandomHash()
{
	$input = "";
	
	if (function_exists("random_bytes"))
	{
		$input = bin2hex(random_bytes(20));
	}
	elseif (function_exists("openssl_random_pseudo_bytes"))
	{
		$input = bin2hex(openssl_random_pseudo_bytes(20));
	}
	else
	{
		$input = time().rand(1, 1000);
	}
	
	return hash("sha256", $input);
}