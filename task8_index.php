<?php
// Задание 8: Защита от SQL-инъекций (все запросы используют prepared statements)
require_once 'config.php';
require_once 'template.php';
require_once 'functions.php';

// Функция для безопасной валидации входных данных
function sanitizeInput($input) {
    if (is_numeric($input)) {
        return intval($input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Получаем все устройства
$devices = getAllDevices($pdo);

// Обработка команд от пользователя
$user_id = 1;

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
    </div>
</body>
</html>';
    exit;
}

// Безопасная обработка POST-запросов с валидацией
if (isset($_POST['button_on']) && isset($_POST['device_id'])) {
    $device_id = sanitizeInput($_POST['device_id']);
    if ($device_id > 0) {
        sendCommand($pdo, $device_id, '1');
        logUserAction($pdo, $user_id, $device_id, 'Включить реле');
    }
}

if (isset($_POST['button_off']) && isset($_POST['device_id'])) {
    $device_id = sanitizeInput($_POST['device_id']);
    if ($device_id > 0) {
        sendCommand($pdo, $device_id, '0');
        logUserAction($pdo, $user_id, $device_id, 'Выключить реле');
    }
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
        .blocked-device { border: 2px solid red; background-color: #ffe6e6; }
        .warning { color: red; font-weight: bold; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Управление устройствами</h1>';

foreach ($devices as $device_id) {
    $device = getDeviceData($pdo, $device_id);
    $is_blocked = isDeviceBlocked($pdo, $device_id);
    
    if ($is_blocked) {
        echo '<div class="device-block blocked-device">
            <h2>Устройство: ' . htmlspecialchars($device['device_name']) . ' (ID: ' . htmlspecialchars($device_id) . ')</h2>
            <p class="warning">⚠ ВНИМАНИЕ: Это устройство заблокировано из-за подозрительного поведения (чрезмерно частые обращения к серверу).</p>
            <p>Температура: ' . htmlspecialchars($device['temperature']) . ' (' . htmlspecialchars($device['temperature_dt']) . ')</p>
            <p>Реле: ' . htmlspecialchars($device['out_state']) . ' (' . htmlspecialchars($device['out_state_dt']) . ')</p>
            <p><em>Управление устройством недоступно.</em></p>
        </div>';
    } else {
        $history_link = "history.php?device_id=" . urlencode($device_id);
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
}

echo '</body></html>';
?>

