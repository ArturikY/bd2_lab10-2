<?php
// Настройки подключения к БД
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$database = 'bd_lab10-2';

// Подключение к базе данных через PDO
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$database", $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>

