<?php
// Прямая проверка работы триггера
require_once 'config.php';
require_once 'functions.php';

echo '<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Тест триггера</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { background: #e8f5e9; padding: 15px; margin: 10px 0; border-left: 4px solid #4CAF50; }
        .error { background: #ffebee; padding: 15px; margin: 10px 0; border-left: 4px solid #f44336; }
        .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; }
        .warning { background: #fff3e0; padding: 15px; margin: 10px 0; border-left: 4px solid #ff9800; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
        button { background: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px; }
        button:hover { background: #45a049; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
    </style>
</head>
<body>
<h1>🧪 Прямой тест триггера блокировки</h1>';

$user_id = 1;

try {
    // 1. Проверяем текст триггера
    echo '<h2>1. Проверка текста триггера</h2>';
    try {
        $query = "SHOW CREATE TRIGGER check_user_activity";
        $stmt = $pdo->query($query);
        $trigger_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trigger_info) {
            echo '<div class="success">✅ Триггер найден!</div>';
            
            // Пробуем разные ключи
            $trigger_sql = null;
            if (isset($trigger_info['SQL Original Statement'])) {
                $trigger_sql = $trigger_info['SQL Original Statement'];
            } elseif (isset($trigger_info['sql_original_statement'])) {
                $trigger_sql = $trigger_info['sql_original_statement'];
            } elseif (isset($trigger_info['Statement'])) {
                $trigger_sql = $trigger_info['Statement'];
            }
            
            if ($trigger_sql) {
                echo '<pre>' . htmlspecialchars($trigger_sql) . '</pre>';
                
                // Проверяем, какая версия триггера (5 секунд или 1 минута)
                if (strpos($trigger_sql, 'INTERVAL 5 SECOND') !== false) {
                    echo '<div class="success">✅ Триггер использует правильный интервал: 5 секунд</div>';
                } elseif (strpos($trigger_sql, 'INTERVAL 1 MINUTE') !== false) {
                    echo '<div class="error">❌ ПРОБЛЕМА: Триггер использует СТАРЫЙ интервал: 1 минута вместо 5 секунд!</div>';
                    echo '<div class="warning">💡 Нужно обновить триггер. Выполните: <code>update_triggers_fast.sql</code> или <code>create_triggers.php</code></div>';
                }
                
                // Проверяем порог блокировки
                if (strpos($trigger_sql, 'action_count > 3') !== false) {
                    echo '<div class="success">✅ Триггер использует правильный порог: > 3 действий</div>';
                } elseif (strpos($trigger_sql, 'action_count > 10') !== false) {
                    echo '<div class="error">❌ ПРОБЛЕМА: Триггер использует СТАРЫЙ порог: > 10 действий вместо 3!</div>';
                    echo '<div class="warning">💡 Нужно обновить триггер.</div>';
                }
            } else {
                echo '<div class="warning">⚠️ Не удалось получить SQL-текст триггера. Ключи массива:</div>';
                echo '<pre>' . print_r(array_keys($trigger_info), true) . '</pre>';
                echo '<pre>' . print_r($trigger_info, true) . '</pre>';
            }
        } else {
            echo '<div class="error">❌ Триггер не найден!</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="error">❌ Ошибка при проверке триггера: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    // 2. Разблокируем пользователя перед тестом
    echo '<h2>2. Подготовка к тесту</h2>';
    $pdo->exec("UPDATE users SET IS_BLOCKED = 0, BLOCKED_UNTIL = NULL WHERE USER_ID = $user_id");
    echo '<div class="success">✅ Пользователь разблокирован</div>';
    
    // 3. Проверяем текущее количество действий за последние 5 секунд
    echo '<h2>3. Текущее состояние</h2>';
    $query = "SELECT COUNT(*) as count, 
              MIN(DATE_TIME) as first_action,
              MAX(DATE_TIME) as last_action
              FROM user_actions 
              WHERE USER_ID = :user_id 
              AND DATE_TIME >= DATE_SUB(NOW(), INTERVAL 5 SECOND)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo '<div class="info">📊 Действий за последние 5 секунд: <strong>' . $current['count'] . '</strong></div>';
    if ($current['first_action']) {
        echo '<div class="info">📅 Первое действие: ' . htmlspecialchars($current['first_action']) . '</div>';
        echo '<div class="info">📅 Последнее действие: ' . htmlspecialchars($current['last_action']) . '</div>';
    }
    
    // 4. Тестируем триггер - добавляем несколько действий быстро
    echo '<h2>4. Тест триггера</h2>';
    
    if (isset($_GET['run_test'])) {
        echo '<div class="info">🔄 Добавляю 5 действий подряд...</div>';
        
        $actions_before = [];
        $actions_after = [];
        
        for ($i = 1; $i <= 5; $i++) {
            // Проверяем статус ДО добавления
            $query = "SELECT IS_BLOCKED, BLOCKED_UNTIL FROM users WHERE USER_ID = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['user_id' => $user_id]);
            $before = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Добавляем действие
            logUserAction($pdo, $user_id, 1, "Тестовое действие $i");
            
            // Небольшая задержка (100ms) между действиями
            usleep(100000);
            
            // Проверяем статус ПОСЛЕ добавления
            $query = "SELECT IS_BLOCKED, BLOCKED_UNTIL FROM users WHERE USER_ID = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['user_id' => $user_id]);
            $after = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $actions_before[] = $before['IS_BLOCKED'];
            $actions_after[] = $after['IS_BLOCKED'];
            
            echo '<div class="info">Действие #' . $i . ': до=' . ($before['IS_BLOCKED'] ? 'ЗАБЛОКИРОВАН' : 'свободен') . 
                 ', после=' . ($after['IS_BLOCKED'] ? 'ЗАБЛОКИРОВАН ✅' : 'свободен') . '</div>';
            
            if ($after['IS_BLOCKED'] == 1) {
                echo '<div class="success">✅ ТРИГГЕР СРАБОТАЛ! Пользователь заблокирован после действия #' . $i . '</div>';
                echo '<div class="info">⏰ Блокировка до: ' . htmlspecialchars($after['BLOCKED_UNTIL']) . '</div>';
                break;
            }
        }
        
        // Итоговая проверка
        $query = "SELECT IS_BLOCKED, BLOCKED_UNTIL FROM users WHERE USER_ID = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        $final = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Проверяем количество действий
        $query = "SELECT COUNT(*) FROM user_actions 
                  WHERE USER_ID = :user_id 
                  AND DATE_TIME >= DATE_SUB(NOW(), INTERVAL 5 SECOND)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        $final_count = $stmt->fetchColumn();
        
        echo '<hr>';
        echo '<h3>📊 Итоговые результаты:</h3>';
        echo '<div class="info">Действий за последние 5 секунд: <strong>' . $final_count . '</strong></div>';
        echo '<div class="info">Статус блокировки: <strong>' . ($final['IS_BLOCKED'] == 1 ? 'ЗАБЛОКИРОВАН' : 'НЕ ЗАБЛОКИРОВАН') . '</strong></div>';
        
        if ($final_count > 3 && $final['IS_BLOCKED'] == 0) {
            echo '<div class="error">❌ ПРОБЛЕМА: Действий больше 3 (' . $final_count . '), но пользователь НЕ заблокирован!</div>';
            echo '<div class="warning">💡 Возможные причины:</div>';
            echo '<ul>';
            echo '<li>Триггер не срабатывает (проверьте права доступа)</li>';
            echo '<li>Триггер использует старые параметры (1 минута вместо 5 секунд)</li>';
            echo '<li>Проблема с логикой триггера</li>';
            echo '</ul>';
        } elseif ($final['IS_BLOCKED'] == 1) {
            echo '<div class="success">✅ Триггер работает правильно! Пользователь заблокирован.</div>';
        } elseif ($final_count <= 3) {
            echo '<div class="info">ℹ️ Действий недостаточно для блокировки (нужно > 3, сейчас ' . $final_count . ')</div>';
        }
        
        echo '<script>setTimeout(function(){location.reload();}, 3000);</script>';
    } else {
        echo '<form method="GET">';
        echo '<button type="submit" name="run_test" value="1">🚀 Запустить тест (добавит 5 действий подряд)</button>';
        echo '</form>';
        echo '<div class="info">💡 Этот тест добавит 5 действий подряд с небольшой задержкой и проверит, срабатывает ли триггер</div>';
    }
    
    // 5. Показываем последние действия
    echo '<h2>5. Последние действия</h2>';
    $query = "SELECT ACTION_ID, ACTION, DATE_TIME, 
              TIMESTAMPDIFF(SECOND, DATE_TIME, NOW()) as seconds_ago
              FROM user_actions 
              WHERE USER_ID = :user_id 
              ORDER BY DATE_TIME DESC 
              LIMIT 10";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($actions) > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Действие</th><th>Время</th><th>Секунд назад</th></tr>';
        foreach ($actions as $action) {
            $color = $action['seconds_ago'] <= 5 ? 'red' : 'black';
            echo '<tr style="color:' . $color . '">';
            echo '<td>' . htmlspecialchars($action['ACTION_ID']) . '</td>';
            echo '<td>' . htmlspecialchars($action['ACTION']) . '</td>';
            echo '<td>' . htmlspecialchars($action['DATE_TIME']) . '</td>';
            echo '<td>' . $action['seconds_ago'] . ' сек</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Подсчитываем действия за последние 5 секунд
        $count_5sec = 0;
        foreach ($actions as $action) {
            if ($action['seconds_ago'] <= 5) {
                $count_5sec++;
            }
        }
        echo '<div class="info">📊 Действий за последние 5 секунд (из показанных): <strong>' . $count_5sec . '</strong></div>';
    }
    
} catch (PDOException $e) {
    echo '<div class="error">❌ Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

echo '<hr>';
echo '<div class="info">';
echo '<a href="debug_blocking.php">← Вернуться к debug_blocking.php</a><br>';
echo '<a href="task6_index.php">task6_index.php</a> - страница для тестирования<br>';
echo '</div>';

echo '</body></html>';
?>

