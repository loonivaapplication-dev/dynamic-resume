<?php
declare(strict_types=1);

$host = '127.0.0.1';
$db   = 'resume_db';
$user = 'root';      // change for your server
$pass = '';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errors throw exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // rows come back as arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // real server-side prepares
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log($e->getMessage());          // log details privately
    http_response_code(500);
    exit('Database connection failed.');  // show nothing sensitive
}