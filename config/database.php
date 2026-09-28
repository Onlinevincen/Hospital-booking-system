<?php
/**
 * Database Connection
 * -----------------------------------------------------
 * Uses PDO with prepared statements everywhere in the app
 * to protect against SQL injection.
 *
 * We use a singleton-style function so every page gets the
 * SAME connection instance instead of opening a new one
 * each time it's included.
 */

// Load environment variables from .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// ---- Database credentials ----
// Load from environment variables, with fallbacks for development
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'city_hospital');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? ''); // XAMPP default is empty password
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

function getDBConnection(): PDO
{
    static $pdo = null; // persists between calls within the same request

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            // Throw exceptions on errors instead of silent failures
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Return rows as associative arrays: $row['column_name']
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Use real prepared statements (not emulated) — safer
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never expose raw DB errors to the end user in production.
            error_log('Database connection failed: ' . $e->getMessage());
            die('Something went wrong connecting to the database. Please try again later.');
        }
    }

    return $pdo;
}
