<?php
declare(strict_types=1);

/**
 * Secure PDO database connection.
 *
 * Teaching note:
 * - Application code should include this file instead of creating new database connections everywhere.
 * - PDO prepared statements will be used in later phases to prevent SQL injection.
 * - In production, move credentials into environment variables or a protected secrets manager.
 */

$dbHost = 'localhost';
$dbName = 'ecommerce_university';
$dbUser = 'root';
$dbPass = '';
$dbCharset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);
} catch (PDOException $exception) {
    /**
     * Never expose database errors to users. Detailed errors can leak server paths,
     * database names, or query information that attackers may use.
     */
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('A database connection error occurred. Please try again later.');
}
