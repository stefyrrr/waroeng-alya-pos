<?php
$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $separator = strpos($line, '=');
        if ($separator === false) {
            continue;
        }

        $name = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));
        if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $name)) {
            continue;
        }

        if (strlen($value) >= 2 && $value[0] === '"' && substr($value, -1) === '"') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_string($decoded)) {
                $value = $decoded;
            }
        } elseif (strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'") {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) === false) {
            putenv($name . '=' . $value);
        }
        if (!isset($_ENV[$name])) {
            $_ENV[$name] = getenv($name) !== false ? getenv($name) : $value;
        }
    }
}

$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db = getenv('DB_NAME');

$host = ($host === false || $host === '') ? '127.0.0.1' : $host;
$user = ($user === false) ? 'root' : $user;
$pass = ($pass === false) ? '' : $pass;
$db = ($db === false || $db === '') ? 'waroeng_alya' : $db;

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
