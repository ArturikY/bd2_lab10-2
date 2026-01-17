<?php
// Задание 6: Триггер блокировки пользователя при частых обращениях
require_once 'config.php';
require_once 'template.php';
require_once 'functions.php';

// Получаем все устройства
$devices = getAllDevices($pdo);

// Обработка команд от пользователя
$user_id = 1; // По умолчанию пользователь с ID = 1

// Проверяем, не заблокирован ли пользователь
if (isUserBlocked($pdo, $user_id)) {
    echo '<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Доступ запрещен</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; text-align: center; }
        .error { color: red; font-size: 24px; margin: 50px; }
    </style>
</head>
<body>
    <div class="error">
        <h1>Доступ запрещен</h1>
        <p>Ваш аккаунт заблокирован из-за чрезмерно частых обращений к устройству.</p>
        <p>Пожалуйста, обратитесь к администратору.</p>
    </div>
</body>
</html>';
    exit;
}

if (isset($_POST['button_on']) && isset($_POST['device_id'])) {
    $device_id = intval($_POST['device_id']);
    sendCommand($pdo, $device_id, '1');
    logUserAction($pdo, $user_id, $device_id, 'Включить реле');
    // Триггер автоматически проверит частоту обращений и заблокирует при необходимости
}

if (isset($_POST['button_off']) && isset($_POST['device_id'])) {
    $device_id = intval($_POST['device_id']);
    sendCommand($pdo, $device_id, '0');
    logUserAction($pdo, $user_id, $device_id, 'Выключить реле');
}

// Формируем интерфейс для всех устройств
echo '<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>MyApp - Все устройства</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .device-block { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Управление устройствами</h1>';

foreach ($devices as $device_id) {
    $device = getDeviceData($pdo, $device_id);
    $history_link = "history.php?device_id=" . $device_id;
    echo renderDeviceTemplate(
        $device['device_name'], 
        $device['temperature'], 
        $device['temperature_dt'], 
        $device['out_state'], 
        $device['out_state_dt'], 
        $device_id,
        $history_link
    );
}

echo '</body></html>';
?>

