<?php
// Задание 4: Усовершенствованный механизм идентификации устройства
require_once 'config.php';
require_once 'template.php';
require_once 'functions.php';

// Улучшенная идентификация через токен устройства
function getDeviceByToken($pdo, $token) {
    $query = "SELECT DEVICE_ID FROM device_table WHERE DEVICE_TOKEN = :token";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['token' => $token]);
    if ($stmt->rowCount() == 1) {
        return $stmt->fetchColumn();
    }
    return null;
}

// Получаем устройство по токену из сессии или параметра
session_start();
$device_id = null;

if (isset($_GET['device_token'])) {
    $device_token = $_GET['device_token'];
    $device_id = getDeviceByToken($pdo, $device_token);
    if ($device_id) {
        $_SESSION['device_token'] = $device_token;
        $_SESSION['device_id'] = $device_id;
    }
} elseif (isset($_SESSION['device_token'])) {
    $device_id = getDeviceByToken($pdo, $_SESSION['device_token']);
    if ($device_id) {
        $_SESSION['device_id'] = $device_id;
    }
} else {
    // Если нет токена, показываем все устройства
    $devices = getAllDevices($pdo);
}

// Обработка команд
if (isset($_POST['button_on']) && isset($_POST['device_id'])) {
    $target_device_id = intval($_POST['device_id']);
    sendCommand($pdo, $target_device_id, '1');
    logUserAction($pdo, 1, $target_device_id, 'Включить реле');
}

if (isset($_POST['button_off']) && isset($_POST['device_id'])) {
    $target_device_id = intval($_POST['device_id']);
    sendCommand($pdo, $target_device_id, '0');
    logUserAction($pdo, 1, $target_device_id, 'Выключить реле');
}

// Формируем интерфейс
echo '<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>MyApp - Управление устройствами</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .device-block { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Управление устройствами</h1>';

if ($device_id) {
    // Показываем одно устройство
    $device = getDeviceData($pdo, $device_id);
    echo renderDeviceTemplate(
        $device['device_name'], 
        $device['temperature'], 
        $device['temperature_dt'], 
        $device['out_state'], 
        $device['out_state_dt'], 
        $device_id
    );
} else {
    // Показываем все устройства
    if (isset($devices)) {
        foreach ($devices as $dev_id) {
            $device = getDeviceData($pdo, $dev_id);
            echo renderDeviceTemplate(
                $device['device_name'], 
                $device['temperature'], 
                $device['temperature_dt'], 
                $device['out_state'], 
                $device['out_state_dt'], 
                $dev_id
            );
        }
    } else {
        echo '<p>Устройство не найдено. Используйте параметр device_token в URL.</p>';
    }
}

echo '</body></html>';
?>

