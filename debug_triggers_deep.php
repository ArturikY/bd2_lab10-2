<?php
// Глубокая диагностика триггеров
require_once 'config.php';
require_once 'functions.php';

echo '<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Глубокая диагностика триггеров</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 10px; max-width: 1000px; margin: 0 auto; }
        .success { background: #4CAF50; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f44336; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #2196F3; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #ff9800; color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 15px; border-left: 4px solid #4CAF50; overflow-x: auto; font-size: 12px; }
        h2 { color: #4CAF50; border-bottom: 2px solid #4CAF50; padding-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
    </style>
</head>
<body>
<div class="container">
<h1>🔬 Глубокая диагностика триггеров</h1>';

$user_id = 1;
$device_id = 1;

try {
    // 1. Показываем текст триггеров
    echo '<h2>1. Текст триггера check_user_activity</h2>';
    try {
        $stmt = $pdo->query("SHOW CREATE TRIGGER check_user_activity");
        $trigger = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trigger) {
            $sql_text = $trigger['SQL Original Statement'] ?? $trigger['Statement'] ?? $trigger['Create Trigger'] ?? '';
            if ($sql_text) {
                echo '<pre>' . htmlspecialchars($sql_text) . '</pre>';
                
                // Проверяем параметры
                if (strpos($sql_text, 'INTERVAL 5 SECOND') === false) {
                    echo '<div class="error">❌ ПРОБЛЕМА: Триггер НЕ использует INTERVAL 5 SECOND!</div>';
                } else {
                    echo '<div class="success">✅ Использует INTERVAL 5 SECOND</div>';
                }
                
                if (strpos($sql_text, 'action_count > 3') === false && strpos($sql_text, 'action_count>3') === false) {
                    echo '<div class="error">❌ ПРОБЛЕМА: Триггер НЕ использует порог > 3!</div>';
                } else {
                    echo '<div class="success">✅ Использует порог > 3</div>';
                }
            } else {
                echo '<div class="error">Не удалось получить текст триггера</div>';
                echo '<pre>' . print_r($trigger, true) . '</pre>';
            }
        }
    } catch (PDOException $e) {
        echo '<div class="error">Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    // 2. Тестируем логику подсчета вручную
    echo '<h2>2. Проверка логики подсчета действий</h2>';
    
    // Очищаем старые тестовые данные
    if (isset($_GET['clear_test'])) {
        $pdo->exec("DELETE FROM user_actions WHERE ACTION LIKE 'Тест%'");
        $pdo->exec("UPDATE users SET IS_BLOCKED = 0, BLOCKED_UNTIL = NULL WHERE USER_ID = $user_id");
        $pdo->exec("UPDATE device_table SET IS_BLOCKED = 0 WHERE DEVICE_ID = $device_id");
        echo '<div class="success">✅ Тестовые данные очищены</div>';
        echo '<script>setTimeout(function(){location.reload();}, 1000);</script>';
    }
    
    // Проверяем текущее количество действий
    $query = "SELECT COUNT(*) as cnt FROM user_actions 
              WHERE USER_ID = :user_id 
              AND DATE_TIME >= DATE_SUB(NOW(), INTERVAL 5 SECOND)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $count_5sec = $stmt->fetchColumn();
    
    echo '<div class="info">📊 Действий пользователя за последние 5 секунд: <strong>' . $count_5sec . '</strong></div>';
    
    $query = "SELECT COUNT(*) as cnt FROM user_actions 
              WHERE DEVICE_ID = :device_id 
              AND DATE_TIME >= DATE_SUB(NOW(), INTERVAL 5 SECOND)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['device_id' => $device_id]);
    $count_device = $stmt->fetchColumn();
    
    echo '<div class="info">📊 Обращений к устройству за последние 5 секунд: <strong>' . $count_device . '</strong></div>';
    
    // Показываем последние действия с временем
    echo '<h2>3. Последние действия с временем</h2>';
    $query = "SELECT ACTION_ID, USER_ID, DEVICE_ID, ACTION, DATE_TIME, 
              TIMESTAMPDIFF(MICROSECOND, DATE_TIME, NOW()) / 1000000 as seconds_ago
              FROM user_actions 
              WHERE USER_ID = :user_id 
              ORDER BY DATE_TIME DESC 
              LIMIT 10";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($actions) > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Действие</th><th>Время</th><th>Секунд назад</th><th>В последних 5 сек?</th></tr>';
        foreach ($actions as $action) {
            $in_range = $action['seconds_ago'] <= 5;
            $color = $in_range ? 'red' : 'black';
            echo '<tr style="color:' . $color . '">';
            echo '<td>' . $action['ACTION_ID'] . '</td>';
            echo '<td>' . htmlspecialchars($action['ACTION']) . '</td>';
            echo '<td>' . $action['DATE_TIME'] . '</td>';
            echo '<td>' . number_format($action['seconds_ago'], 3) . '</td>';
            echo '<td>' . ($in_range ? '✅ ДА' : '❌ НЕТ') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    // 4. Тестируем триггер напрямую
    echo '<h2>4. Тест триггера с логированием</h2>';
    
    if (isset($_GET['test_detailed'])) {
        echo '<div class="info">🔄 Выполняю детальный тест...</div>';
        
        // Разблокируем
        $pdo->exec("UPDATE users SET IS_BLOCKED = 0, BLOCKED_UNTIL = NULL WHERE USER_ID = $user_id");
        $pdo->exec("UPDATE device_table SET IS_BLOCKED = 0 WHERE DEVICE_ID = $device_id");
        
        // Проверяем статус ДО
        $stmt = $pdo->prepare("SELECT IS_BLOCKED FROM users WHERE USER_ID = ?");
        $stmt->execute([$user_id]);
        $user_before = $stmt->fetchColumn();
        
        echo '<div class="info">Статус ДО: Пользователь=' . ($user_before ? 'заблокирован' : 'свободен') . '</div>';
        
        // Добавляем действие и сразу проверяем
        for ($i = 1; $i <= 5; $i++) {
            // Время ДО добавления
            $time_before = time();
            
            // Добавляем действие
            logUserAction($pdo, $user_id, $device_id, "Детальный тест $i");
            
            // Время ПОСЛЕ добавления
            $time_after = time();
            
            // Сразу проверяем количество действий за 5 секунд (как в триггере)
            $query = "SELECT COUNT(*) FROM user_actions 
                      WHERE USER_ID = :user_id 
                      AND DATE_TIME >= DATE_SUB(NOW(), INTERVAL 5 SECOND)";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['user_id' => $user_id]);
            $count_after = $stmt->fetchColumn();
            
            // Проверяем статус блокировки
            $stmt = $pdo->prepare("SELECT IS_BLOCKED, BLOCKED_UNTIL FROM users WHERE USER_ID = ?");
            $stmt->execute([$user_id]);
            $user_status = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo '<div class="info">После действия #' . $i . ': Действий за 5 сек=' . $count_after . 
                 ', Блокирован=' . ($user_status['IS_BLOCKED'] ? 'ДА ✅' : 'НЕТ ❌') . '</div>';
            
            if ($user_status['IS_BLOCKED'] == 1) {
                echo '<div class="success">✅ ТРИГГЕР СРАБОТАЛ на действии #' . $i . '!</div>';
                break;
            }
            
            // Небольшая задержка
            usleep(100000); // 100ms
        }
        
        echo '<script>setTimeout(function(){location.href="debug_triggers_deep.php";}, 3000);</script>';
    } else {
        echo '<a href="?test_detailed=1"><button style="background:#4CAF50;color:white;padding:10px 20px;border:none;cursor:pointer;">🧪 Запустить детальный тест</button></a>';
        echo '<a href="?clear_test=1"><button style="background:#ff9800;color:white;padding:10px 20px;border:none;cursor:pointer;margin-left:10px;">🗑️ Очистить тестовые данные</button></a>';
    }
    
    // 5. Проверяем структуру таблицы user_actions
    echo '<h2>5. Структура таблицы user_actions</h2>';
    try {
        $stmt = $pdo->query("DESCRIBE user_actions");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<table>';
        echo '<tr><th>Поле</th><th>Тип</th><th>NULL</th><th>Ключ</th></tr>';
        foreach ($columns as $col) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Проверяем тип DATE_TIME
        foreach ($columns as $col) {
            if ($col['Field'] == 'DATE_TIME') {
                if (strpos(strtolower($col['Type']), 'datetime') === false) {
                    echo '<div class="error">❌ ПРОБЛЕМА: Поле DATE_TIME имеет тип ' . $col['Type'] . ', а не DATETIME!</div>';
                } else {
                    echo '<div class="success">✅ Поле DATE_TIME имеет правильный тип</div>';
                }
            }
        }
    } catch (PDOException $e) {
        echo '<div class="error">Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    // 6. Тест NOW() функции
    echo '<h2>6. Проверка функции NOW()</h2>';
    $stmt = $pdo->query("SELECT NOW() as `current_time`, DATE_SUB(NOW(), INTERVAL 5 SECOND) as `five_seconds_ago`");
    $time_test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo '<div class="info">Текущее время (NOW()): <strong>' . $time_test['current_time'] . '</strong></div>';
    echo '<div class="info">5 секунд назад: <strong>' . $time_test['five_seconds_ago'] . '</strong></div>';
    
} catch (PDOException $e) {
    echo '<div class="error">❌ Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

echo '</div></body></html>';
?>

