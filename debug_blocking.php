<?php
// Диагностический скрипт для проверки блокировки пользователя
require_once 'config.php';

echo '<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Диагностика блокировки</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .info { background: #e3f2fd; padding: 10px; margin: 10px 0; border-left: 4px solid #2196F3; }
        .success { background: #e8f5e9; padding: 10px; margin: 10px 0; border-left: 4px solid #4CAF50; }
        .error { background: #ffebee; padding: 10px; margin: 10px 0; border-left: 4px solid #f44336; }
        .warning { background: #fff3e0; padding: 10px; margin: 10px 0; border-left: 4px solid #ff9800; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        h2 { color: #333; }
        .test-button { background: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px; }
        .test-button:hover { background: #45a049; }
    </style>
</head>
<body>
<h1>🔍 Диагностика блокировки пользователя</h1>';

$user_id = 1;

// 1. Проверяем существование триггера
echo '<h2>1. Проверка триггеров</h2>';
try {
    // Получаем ВСЕ триггеры (без LIKE, так как он может не работать в некоторых версиях MySQL)
    $query = "SHOW TRIGGERS";
    $stmt = $pdo->query($query);
    
    // Пробуем разные варианты получения данных (PDO может возвращать ключи в разном регистре)
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Проверяем, какие ключи возвращаются (для отладки)
    if (count($triggers) > 0 && count($triggers[0]) > 0) {
        $first_trigger_keys = array_keys($triggers[0]);
        echo '<div class="info">🔍 Найдено триггеров: <strong>' . count($triggers) . '</strong></div>';
        echo '<div class="info">🔍 Ключи массива: <code>' . implode(', ', $first_trigger_keys) . '</code></div>';
    }
    
    // Ищем нужные триггеры (проверяем разные варианты названий ключей)
    $found_user_trigger = false;
    $found_device_trigger = false;
    $user_trigger_data = null;
    $device_trigger_data = null;
    
    foreach ($triggers as $trigger) {
        // Проверяем разные варианты ключей (Trigger, TRIGGER, trigger)
        $trigger_name = null;
        if (isset($trigger['Trigger'])) {
            $trigger_name = $trigger['Trigger'];
        } elseif (isset($trigger['TRIGGER'])) {
            $trigger_name = $trigger['TRIGGER'];
        } elseif (isset($trigger['trigger'])) {
            $trigger_name = $trigger['trigger'];
        }
        
        if ($trigger_name) {
            // Игнорируем регистр при сравнении
            if (strcasecmp($trigger_name, 'check_user_activity') == 0) {
                $found_user_trigger = true;
                $user_trigger_data = $trigger;
            }
            if (strcasecmp($trigger_name, 'check_device_activity') == 0) {
                $found_device_trigger = true;
                $device_trigger_data = $trigger;
            }
        }
    }
    
    // Выводим результаты
    if ($found_user_trigger || $found_device_trigger || count($triggers) > 0) {
        echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%; margin: 10px 0;">';
        echo '<tr style="background: #4CAF50; color: white;">';
        
        // Определяем заголовки на основе первого триггера
        if (count($triggers) > 0) {
            $keys = array_keys($triggers[0]);
            foreach ($keys as $key) {
                echo '<th>' . htmlspecialchars($key) . '</th>';
            }
        } else {
            echo '<th>Триггер</th><th>Таблица</th><th>Событие</th><th>Время</th>';
        }
        echo '</tr>';
        
        foreach ($triggers as $trigger) {
            $highlight = '';
            $trigger_name = '';
            if (isset($trigger['Trigger'])) {
                $trigger_name = $trigger['Trigger'];
            } elseif (isset($trigger['TRIGGER'])) {
                $trigger_name = $trigger['TRIGGER'];
            } elseif (isset($trigger['trigger'])) {
                $trigger_name = $trigger['trigger'];
            }
            
            if ($trigger_name && (strcasecmp($trigger_name, 'check_user_activity') == 0 || 
                                  strcasecmp($trigger_name, 'check_device_activity') == 0)) {
                $highlight = ' style="background:#e8f5e9;"';
            }
            
            echo '<tr' . $highlight . '>';
            foreach ($trigger as $key => $value) {
                echo '<td>' . htmlspecialchars($value) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    }
    
    // Выводим статус поиска
    if ($found_user_trigger) {
        echo '<div class="success">✅ Триггер <code>check_user_activity</code> существует!</div>';
    } else {
        echo '<div class="error">❌ Триггер <code>check_user_activity</code> НЕ НАЙДЕН!</div>';
        if (count($triggers) == 0) {
            echo '<div class="warning">⚠️ В базе данных нет триггеров вообще. Триггер не создан.</div>';
        } else {
            echo '<div class="warning">⚠️ Триггер существует в phpMyAdmin, но PHP не может его найти. Возможна проблема с правами доступа или названием.</div>';
            echo '<div class="info">💡 Найденные триггеры: ';
            $trigger_names = [];
            foreach ($triggers as $t) {
                if (isset($t['Trigger'])) $trigger_names[] = $t['Trigger'];
                elseif (isset($t['TRIGGER'])) $trigger_names[] = $t['TRIGGER'];
                elseif (isset($t['trigger'])) $trigger_names[] = $t['trigger'];
            }
            echo '<code>' . implode(', ', $trigger_names) . '</code></div>';
        }
    }
    
    if ($found_device_trigger) {
        echo '<div class="success">✅ Триггер <code>check_device_activity</code> существует!</div>';
    } else {
        echo '<div class="warning">⚠️ Триггер <code>check_device_activity</code> не найден</div>';
    }
    
} catch (PDOException $e) {
    echo '<div class="error">❌ Ошибка при проверке триггера: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<div class="info">💡 Попробуйте выполнить в MySQL: <code>SHOW TRIGGERS;</code> вручную</div>';
}

// 2. Проверяем статус пользователя
echo '<h2>2. Статус пользователя</h2>';
try {
    $query = "SELECT USER_ID, USERNAME, IS_BLOCKED, BLOCKED_UNTIL FROM users WHERE USER_ID = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo '<table>';
        echo '<tr><th>Параметр</th><th>Значение</th></tr>';
        echo '<tr><td>USER_ID</td><td>' . htmlspecialchars($user['USER_ID']) . '</td></tr>';
        echo '<tr><td>USERNAME</td><td>' . htmlspecialchars($user['USERNAME']) . '</td></tr>';
        echo '<tr><td>IS_BLOCKED</td><td>' . ($user['IS_BLOCKED'] == 1 ? '<span style="color:red;font-weight:bold">ДА (заблокирован)</span>' : '<span style="color:green">НЕТ (не заблокирован)</span>') . '</td></tr>';
        echo '<tr><td>BLOCKED_UNTIL</td><td>' . ($user['BLOCKED_UNTIL'] ? htmlspecialchars($user['BLOCKED_UNTIL']) : 'NULL') . '</td></tr>';
        echo '</table>';
        
        if ($user['IS_BLOCKED'] == 1) {
            echo '<div class="error">⚠️ Пользователь ЗАБЛОКИРОВАН!</div>';
        } else {
            echo '<div class="success">✅ Пользователь НЕ заблокирован</div>';
        }
    } else {
        echo '<div class="error">❌ Пользователь с ID=' . $user_id . ' не найден!</div>';
    }
} catch (PDOException $e) {
    echo '<div class="error">❌ Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// 3. Проверяем количество действий за последние 5 секунд
echo '<h2>3. Действия пользователя (последние 5 секунд)</h2>';
try {
    $query = "SELECT COUNT(*) as count FROM user_actions 
              WHERE USER_ID = :user_id 
              AND DATE_TIME >= DATE_SUB(NOW(), INTERVAL 5 SECOND)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo '<div class="info">📊 Количество действий за последние 5 секунд: <strong>' . $count . '</strong></div>';
    
    if ($count > 3) {
        echo '<div class="warning">⚠️ Действий больше 3! Пользователь ДОЛЖЕН быть заблокирован!</div>';
    } else {
        echo '<div class="info">ℹ️ Для блокировки нужно более 3 действий за 5 секунд. Сейчас: ' . $count . '</div>';
    }
    
    // Показываем последние 10 действий
    $query = "SELECT ACTION_ID, DEVICE_ID, ACTION, DATE_TIME 
              FROM user_actions 
              WHERE USER_ID = :user_id 
              ORDER BY DATE_TIME DESC 
              LIMIT 10";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($actions) > 0) {
        echo '<h3>Последние 10 действий:</h3>';
        echo '<table><tr><th>ID</th><th>Устройство</th><th>Действие</th><th>Время</th><th>Прошло секунд</th></tr>';
        foreach ($actions as $action) {
            $time_diff = time() - strtotime($action['DATE_TIME']);
            $color = $time_diff <= 5 ? 'red' : 'black';
            echo '<tr style="color:' . $color . '">';
            echo '<td>' . htmlspecialchars($action['ACTION_ID']) . '</td>';
            echo '<td>' . htmlspecialchars($action['DEVICE_ID']) . '</td>';
            echo '<td>' . htmlspecialchars($action['ACTION']) . '</td>';
            echo '<td>' . htmlspecialchars($action['DATE_TIME']) . '</td>';
            echo '<td>' . $time_diff . ' сек назад</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<div class="warning">⚠️ Действия не найдены. Нажмите кнопки на странице task6_index.php</div>';
    }
} catch (PDOException $e) {
    echo '<div class="error">❌ Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// 4. Тестирование триггера напрямую
echo '<h2>4. Тестирование работы триггера</h2>';

// Пробуем напрямую проверить, работает ли триггер
// Добавляем тестовое действие и проверяем, блокируется ли пользователь
if (isset($_GET['test_trigger'])) {
    try {
        require_once 'functions.php';
        
        echo '<div class="info">🔬 Проверка работы триггера напрямую...</div>';
        
        // Проверяем текущий статус блокировки
        $query = "SELECT IS_BLOCKED FROM users WHERE USER_ID = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        $before_blocked = $stmt->fetchColumn();
        
        echo '<div class="info">📊 Статус блокировки ДО добавления действия: <strong>' . ($before_blocked == 1 ? 'ЗАБЛОКИРОВАН' : 'НЕ ЗАБЛОКИРОВАН') . '</strong></div>';
        
        // Добавляем действие (триггер должен сработать)
        logUserAction($pdo, $user_id, 1, 'Тест работы триггера');
        
        // Проверяем статус блокировки ПОСЛЕ
        $query = "SELECT IS_BLOCKED, BLOCKED_UNTIL FROM users WHERE USER_ID = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        $after = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo '<div class="info">📊 Статус блокировки ПОСЛЕ добавления действия: <strong>' . ($after['IS_BLOCKED'] == 1 ? 'ЗАБЛОКИРОВАН' : 'НЕ ЗАБЛОКИРОВАН') . '</strong></div>';
        
        if ($after['IS_BLOCKED'] == 1) {
            echo '<div class="success">✅ ТРИГГЕР РАБОТАЕТ! Пользователь заблокирован после добавления действия!</div>';
            echo '<div class="info">⏰ Блокировка до: ' . htmlspecialchars($after['BLOCKED_UNTIL']) . '</div>';
        } else {
            // Проверяем количество действий
            $query = "SELECT COUNT(*) FROM user_actions 
                      WHERE USER_ID = :user_id 
                      AND DATE_TIME >= DATE_SUB(NOW(), INTERVAL 5 SECOND)";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['user_id' => $user_id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 3) {
                echo '<div class="error">❌ ПРОБЛЕМА: Действий больше 3 (' . $count . '), но пользователь НЕ заблокирован. Триггер не срабатывает!</div>';
            } else {
                echo '<div class="info">ℹ️ Действий за последние 5 секунд: ' . $count . ' (нужно более 3 для блокировки)</div>';
                echo '<div class="info">💡 Добавьте еще действий, чтобы проверить триггер</div>';
            }
        }
        
        echo '<script>setTimeout(function(){location.reload();}, 2000);</script>';
        
    } catch (PDOException $e) {
        echo '<div class="error">❌ Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

echo '<form method="GET">';
echo '<button type="submit" name="test_trigger" value="1" class="test-button" style="background:#ff9800;">🧪 Проверить работу триггера</button>';
echo '</form>';

// 5. Тестовая функция - добавить действие
echo '<h2>5. Тестирование</h2>';
if (isset($_GET['test_action'])) {
    try {
        require_once 'functions.php';
        logUserAction($pdo, $user_id, 1, 'Тестовое действие');
        echo '<div class="success">✅ Действие добавлено! Обновите страницу для проверки.</div>';
        echo '<script>setTimeout(function(){location.reload();}, 1000);</script>';
    } catch (PDOException $e) {
        echo '<div class="error">❌ Ошибка при добавлении действия: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

echo '<div class="info">';
echo '<strong>Как протестировать:</strong><br>';
echo '1. Откройте эту страницу и <strong>task6_index.php</strong> в разных вкладках<br>';
echo '2. На странице task6_index.php быстро нажмите кнопки 4-5 раз<br>';
echo '3. ИЛИ используйте кнопку ниже для быстрого тестирования<br>';
echo '4. Обновите эту страницу для проверки результата<br>';
echo '</div>';

echo '<form method="GET">';
echo '<button type="submit" name="test_action" value="1" class="test-button">➕ Добавить тестовое действие</button>';
echo '</form>';

echo '<h2>5. Быстрое добавление нескольких действий</h2>';
if (isset($_GET['add_many'])) {
    $count = intval($_GET['count'] ?? 5);
    try {
        require_once 'functions.php';
        for ($i = 0; $i < $count; $i++) {
            logUserAction($pdo, $user_id, 1, 'Тестовое действие ' . ($i + 1));
            usleep(100000); // Небольшая задержка 0.1 сек между действиями
        }
        echo '<div class="success">✅ Добавлено ' . $count . ' действий! Обновите страницу для проверки.</div>';
        echo '<script>setTimeout(function(){location.reload();}, 1000);</script>';
    } catch (PDOException $e) {
        echo '<div class="error">❌ Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

echo '<form method="GET">';
echo '<label>Количество действий: <input type="number" name="count" value="5" min="1" max="10"></label><br><br>';
echo '<button type="submit" name="add_many" value="1" class="test-button">⚡ Добавить несколько действий быстро</button>';
echo '</form>';

// 6. Разблокировка пользователя
if (isset($_GET['unblock'])) {
    try {
        $query = "UPDATE users SET IS_BLOCKED = 0, BLOCKED_UNTIL = NULL WHERE USER_ID = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        echo '<div class="success">✅ Пользователь разблокирован! Обновите страницу.</div>';
        echo '<script>setTimeout(function(){location.reload();}, 1000);</script>';
    } catch (PDOException $e) {
        echo '<div class="error">❌ Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

echo '<br><form method="GET">';
echo '<button type="submit" name="unblock" value="1" class="test-button" style="background:#ff9800;">🔓 Разблокировать пользователя</button>';
echo '</form>';

echo '<br><hr>';
echo '<div class="info">';
echo '<strong>🔗 Полезные ссылки:</strong><br>';
echo '<a href="task6_index.php" target="_blank">task6_index.php</a> - страница для тестирования блокировки<br>';
echo '<a href="fix_triggers.php" target="_blank" style="font-weight:bold;color:#f44336;font-size:16px;">🔧 fix_triggers.php</a> - <strong>ИСПРАВЛЕНИЕ ТРИГГЕРОВ (НАЧНИТЕ ОТСЮДА!)</strong><br>';
echo '<a href="test_trigger_directly.php" target="_blank">test_trigger_directly.php</a> - прямой тест триггера<br>';
echo '<a href="create_triggers.php" target="_blank">create_triggers.php</a> - альтернативное создание триггеров<br>';
echo '<a href="create_triggers_simple.sql">create_triggers_simple.sql</a> - SQL-скрипт для ручного выполнения<br>';
echo '</div>';

echo '</body></html>';
?>

