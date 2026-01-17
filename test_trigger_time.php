<?php
// Тест проблемы с временем
require_once 'config.php';
require_once 'functions.php';

echo '<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Тест времени триггера</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 10px; max-width: 900px; margin: 0 auto; }
        .success { background: #4CAF50; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f44336; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #2196F3; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #ff9800; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 15px; border-left: 4px solid #4CAF50; overflow-x: auto; }
        h2 { color: #4CAF50; border-bottom: 2px solid #4CAF50; padding-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
    </style>
</head>
<body>
<div class="container">
<h1>🕐 Тест проблемы с временем в триггере</h1>';

$user_id = 1;
$device_id = 1;

try {
    // 1. Проверяем время в БД и PHP
    echo '<h2>1. Сравнение времени</h2>';
    
    $stmt = $pdo->query("SELECT NOW() as db_time");
    $db_time = $stmt->fetch(PDO::FETCH_ASSOC)['db_time'];
    $php_time = date('Y-m-d H:i:s');
    
    echo '<div class="info">Время PHP (сервера): <strong>' . $php_time . '</strong></div>';
    echo '<div class="info">Время MySQL (БД): <strong>' . $db_time . '</strong></div>';
    
    $diff = abs(strtotime($db_time) - strtotime($php_time));
    if ($diff > 5) {
        echo '<div class="error">❌ ПРОБЛЕМА: Разница во времени между PHP и MySQL: ' . $diff . ' секунд!</div>';
    } else {
        echo '<div class="success">✅ Время синхронизировано (разница: ' . $diff . ' сек)</div>';
    }
    
    // 2. Проверяем как logUserAction записывает время
    echo '<h2>2. Проверка функции logUserAction</h2>';
    
    if (isset($_GET['test_insert'])) {
        // Разблокируем
        $pdo->exec("UPDATE users SET IS_BLOCKED = 0, BLOCKED_UNTIL = NULL WHERE USER_ID = $user_id");
        $pdo->exec("UPDATE device_table SET IS_BLOCKED = 0 WHERE DEVICE_ID = $device_id");
        
        echo '<div class="info">🔄 Добавляю действие и сразу проверяю...</div>';
        
        // Время ДО добавления
        $stmt = $pdo->query("SELECT NOW() as time_before");
        $time_before = $stmt->fetch(PDO::FETCH_ASSOC)['time_before'];
        
        // Добавляем действие
        logUserAction($pdo, $user_id, $device_id, "Тест времени");
        
        // Время ПОСЛЕ добавления
        $stmt = $pdo->query("SELECT NOW() as time_after");
        $time_after = $stmt->fetch(PDO::FETCH_ASSOC)['time_after'];
        
        // Получаем записанное время
        $stmt = $pdo->query("SELECT DATE_TIME FROM user_actions ORDER BY ACTION_ID DESC LIMIT 1");
        $recorded_time = $stmt->fetchColumn();
        
        echo '<div class="info">Время ДО добавления: ' . $time_before . '</div>';
        echo '<div class="info">Время ПОСЛЕ добавления: ' . $time_after . '</div>';
        echo '<div class="info">Время записанное в БД: <strong>' . $recorded_time . '</strong></div>';
        
        // Проверяем, входит ли время в диапазон
        $recorded_timestamp = strtotime($recorded_time);
        $before_timestamp = strtotime($time_before);
        $after_timestamp = strtotime($time_after);
        
        if ($recorded_timestamp >= $before_timestamp && $recorded_timestamp <= $after_timestamp) {
            echo '<div class="success">✅ Время записано правильно!</div>';
        } else {
            echo '<div class="error">❌ ПРОБЛЕМА: Время записано неправильно!</div>';
        }
        
        // Проверяем подсчет за последние 5 секунд
        $query = "SELECT COUNT(*) FROM user_actions 
                  WHERE USER_ID = :user_id 
                  AND DATE_TIME >= DATE_SUB(NOW(), INTERVAL 5 SECOND)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        $count = $stmt->fetchColumn();
        
        echo '<div class="info">Действий за последние 5 секунд (с учетом нового): <strong>' . $count . '</strong></div>';
        
        if ($count >= 1) {
            echo '<div class="success">✅ Триггер должен видеть это действие!</div>';
        } else {
            echo '<div class="error">❌ ПРОБЛЕМА: Триггер НЕ видит действие в последних 5 секундах!</div>';
            echo '<div class="warning">Возможные причины:</div>';
            echo '<ul>';
            echo '<li>Время в БД сильно отличается от NOW()</li>';
            echo '<li>Проблема с часовым поясом</li>';
            echo '<li>DATE_TIME записывается неправильно</li>';
            echo '</ul>';
        }
        
        echo '<script>setTimeout(function(){location.reload();}, 2000);</script>';
    } else {
        echo '<a href="?test_insert=1"><button style="background:#4CAF50;color:white;padding:10px 20px;border:none;cursor:pointer;">🧪 Протестировать запись времени</button></a>';
    }
    
    // 3. Тест триггера с быстрыми действиями
    echo '<h2>3. Тест триггера с быстрыми действиями</h2>';
    
    if (isset($_GET['test_fast'])) {
        // Очищаем старые тесты
        $pdo->exec("DELETE FROM user_actions WHERE ACTION LIKE 'Быстрый тест%'");
        $pdo->exec("UPDATE users SET IS_BLOCKED = 0, BLOCKED_UNTIL = NULL WHERE USER_ID = $user_id");
        $pdo->exec("UPDATE device_table SET IS_BLOCKED = 0 WHERE DEVICE_ID = $device_id");
        
        echo '<div class="info">🔄 Добавляю 5 действий ОЧЕНЬ быстро (без задержки)...</div>';
        
        for ($i = 1; $i <= 5; $i++) {
            // Проверяем количество ДО
            $query = "SELECT COUNT(*) FROM user_actions 
                      WHERE USER_ID = :user_id 
                      AND DATE_TIME >= DATE_SUB(NOW(), INTERVAL 5 SECOND)";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['user_id' => $user_id]);
            $count_before = $stmt->fetchColumn();
            
            // Добавляем действие
            logUserAction($pdo, $user_id, $device_id, "Быстрый тест $i");
            
            // Сразу проверяем количество ПОСЛЕ
            $query = "SELECT COUNT(*) FROM user_actions 
                      WHERE USER_ID = :user_id 
                      AND DATE_TIME >= DATE_SUB(NOW(), INTERVAL 5 SECOND)";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['user_id' => $user_id]);
            $count_after = $stmt->fetchColumn();
            
            // Проверяем статус блокировки
            $stmt = $pdo->prepare("SELECT IS_BLOCKED FROM users WHERE USER_ID = ?");
            $stmt->execute([$user_id]);
            $blocked = $stmt->fetchColumn();
            
            echo '<div class="info">Действие #' . $i . ': Действий ДО=' . $count_before . 
                 ', ПОСЛЕ=' . $count_after . ', Блокирован=' . ($blocked ? 'ДА ✅' : 'НЕТ ❌') . '</div>';
            
            if ($blocked) {
                echo '<div class="success">✅ ТРИГГЕР СРАБОТАЛ на действии #' . $i . '!</div>';
                break;
            }
        }
        
        echo '<script>setTimeout(function(){location.reload();}, 3000);</script>';
    } else {
        echo '<a href="?test_fast=1"><button style="background:#2196F3;color:white;padding:10px 20px;border:none;cursor:pointer;">⚡ Быстрый тест (5 действий без задержки)</button></a>';
    }
    
} catch (PDOException $e) {
    echo '<div class="error">❌ Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

echo '</div></body></html>';
?>

