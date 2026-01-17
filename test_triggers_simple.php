<?php
// ПРОСТОЙ ТЕСТ ТРИГГЕРОВ - работает без лишнего
require_once 'config.php';
require_once 'functions.php';

echo '<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Простой тест триггеров</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 10px; max-width: 800px; margin: 0 auto; }
        .success { background: #4CAF50; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f44336; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #2196F3; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #ff9800; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        button { background: #4CAF50; color: white; padding: 15px 30px; border: none; cursor: pointer; font-size: 16px; border-radius: 5px; margin: 5px; }
        button:hover { background: #45a049; }
        .danger { background: #f44336; }
        .danger:hover { background: #d32f2f; }
        pre { background: #f5f5f5; padding: 10px; border-left: 4px solid #4CAF50; overflow-x: auto; }
        h1 { color: #333; }
        h2 { color: #4CAF50; border-bottom: 2px solid #4CAF50; padding-bottom: 5px; }
    </style>
</head>
<body>
<div class="container">
<h1>🧪 Простой тест триггеров</h1>';

$user_id = 1;
$device_id = 1;

try {
    // 1. Проверяем триггеры
    echo '<h2>1. Проверка триггеров</h2>';
    $stmt = $pdo->query("SHOW TRIGGERS");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_user_trigger = false;
    $has_device_trigger = false;
    
    foreach ($triggers as $t) {
        $name = $t['Trigger'] ?? $t['TRIGGER'] ?? '';
        if (strcasecmp($name, 'check_user_activity') == 0) $has_user_trigger = true;
        if (strcasecmp($name, 'check_device_activity') == 0) $has_device_trigger = true;
    }
    
    if ($has_user_trigger && $has_device_trigger) {
        echo '<div class="success">✅ Оба триггера найдены!</div>';
    } else {
        if (!$has_user_trigger) echo '<div class="error">❌ Триггер check_user_activity НЕ НАЙДЕН</div>';
        if (!$has_device_trigger) echo '<div class="error">❌ Триггер check_device_activity НЕ НАЙДЕН</div>';
        echo '<div class="warning">⚠️ Выполните файл <strong>TRIGGERS_FIX_FINAL.sql</strong> в phpMyAdmin</div>';
        echo '</div></body></html>';
        exit;
    }
    
    // 2. Разблокируем перед тестом
    if (isset($_GET['unblock'])) {
        $pdo->exec("UPDATE users SET IS_BLOCKED = 0, BLOCKED_UNTIL = NULL WHERE USER_ID = $user_id");
        $pdo->exec("UPDATE device_table SET IS_BLOCKED = 0 WHERE DEVICE_ID = $device_id");
        echo '<div class="success">✅ Разблокировано</div>';
        echo '<script>setTimeout(function(){location.href="test_triggers_simple.php";}, 1000);</script>';
    }
    
    // 3. Тестируем
    if (isset($_GET['test'])) {
        echo '<h2>2. Тест триггеров</h2>';
        echo '<div class="info">Добавляю 5 действий подряд...</div>';
        
        // Разблокируем
        $pdo->exec("UPDATE users SET IS_BLOCKED = 0, BLOCKED_UNTIL = NULL WHERE USER_ID = $user_id");
        $pdo->exec("UPDATE device_table SET IS_BLOCKED = 0 WHERE DEVICE_ID = $device_id");
        
        $blocked_user = false;
        $blocked_device = false;
        
        for ($i = 1; $i <= 5; $i++) {
            // Добавляем действие
            logUserAction($pdo, $user_id, $device_id, "Тест $i");
            
            // Небольшая задержка
            usleep(50000);
            
            // Проверяем статус
            $stmt = $pdo->prepare("SELECT IS_BLOCKED FROM users WHERE USER_ID = ?");
            $stmt->execute([$user_id]);
            $user_blocked = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT IS_BLOCKED FROM device_table WHERE DEVICE_ID = ?");
            $stmt->execute([$device_id]);
            $device_blocked = $stmt->fetchColumn();
            
            echo '<div class="info">Действие #' . $i . ': Пользователь=' . ($user_blocked ? '🔒 ЗАБЛОКИРОВАН' : '✅ свободен') . 
                 ', Устройство=' . ($device_blocked ? '🔒 ЗАБЛОКИРОВАН' : '✅ свободен') . '</div>';
            
            if ($user_blocked) $blocked_user = true;
            if ($device_blocked) $blocked_device = true;
            
            if ($user_blocked || $device_blocked) break;
        }
        
        // Итог
        echo '<hr>';
        if ($blocked_user) {
            echo '<div class="success">✅ ТРИГГЕР ПОЛЬЗОВАТЕЛЯ РАБОТАЕТ!</div>';
        } else {
            echo '<div class="error">❌ Триггер пользователя НЕ СРАБОТАЛ</div>';
        }
        
        if ($blocked_device) {
            echo '<div class="success">✅ ТРИГГЕР УСТРОЙСТВА РАБОТАЕТ!</div>';
        } else {
            echo '<div class="error">❌ Триггер устройства НЕ СРАБОТАЛ</div>';
        }
        
        echo '<script>setTimeout(function(){location.href="test_triggers_simple.php";}, 3000);</script>';
    } else {
        // Показываем кнопки
        echo '<h2>2. Действия</h2>';
        echo '<a href="?test=1"><button>🧪 Запустить тест (добавит 5 действий)</button></a>';
        echo '<a href="?unblock=1"><button class="danger">🔓 Разблокировать все</button></a>';
    }
    
    // 4. Текущий статус
    echo '<h2>3. Текущий статус</h2>';
    $stmt = $pdo->prepare("SELECT IS_BLOCKED FROM users WHERE USER_ID = ?");
    $stmt->execute([$user_id]);
    $user_status = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT IS_BLOCKED FROM device_table WHERE DEVICE_ID = ?");
    $stmt->execute([$device_id]);
    $device_status = $stmt->fetchColumn();
    
    echo '<div class="info">Пользователь: ' . ($user_status ? '🔒 ЗАБЛОКИРОВАН' : '✅ свободен') . '</div>';
    echo '<div class="info">Устройство: ' . ($device_status ? '🔒 ЗАБЛОКИРОВАН' : '✅ свободен') . '</div>';
    
} catch (Exception $e) {
    echo '<div class="error">Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

echo '</div></body></html>';
?>

