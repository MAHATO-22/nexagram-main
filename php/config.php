<?php
// config.php - Nexagram Database Connection

$host = 'localhost';
$db   = 'nexa'; // Database နာမည်ကို nexa ပြောင်းလိုက်ပါပြီ
$user = 'root';     // XAMPP default username
$pass = '';         // XAMPP default password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>