<?php

$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
	$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); // Read file into array of lines
	foreach ($lines as $line) {
		[$name, $value] = explode('=', $line, 2); // Split line into name and value
		$name = trim($name);
		$value = trim($value);
		if (!array_key_exists($name, $_ENV)) { // Set only if not already set
			$_ENV[$name] = $value;
		}
		putenv($name . '=' . $value); // Also set in the environment variables
	}
}

# default values if not set in .env
$dbUser = getenv('DB_USER') !== false ? getenv('DB_USER') : 'docker';
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'docker';
$dbHost = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'db';
$dbName = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'db';

# Define constants
define('USERNAME', $dbUser);
define('PASSWORD', $dbPass);
define('HOST', $dbHost);
define('DATABASE', $dbName);
