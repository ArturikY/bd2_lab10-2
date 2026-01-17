<?php
// PHP-скрипт для создания триггеров (работает надежнее чем SQL-файл)
require_once 'config.php';

echo '<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Создание триггеров</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { background: #e8f5e9; padding: 10px; margin: 10px 0; border-left: 4px solid #4CAF50; }
        .error { background: #ffebee; padding: 10px; margin: 10px 0; border-left: 4px solid #f44336; }
        .info { background: #e3f2fd; padding: 10px; margin: 10px 0; border-left: 4px solid #2196F3; }
        .warning { background: #fff3e0; padding: 10px; margin: 10px 0; border-left: 4px solid #ff9800; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
        h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 5px; }
    </style>
</head>
<body>
<h1>🔧 Создание триггеров блокировки</h1>';

try {
    // Проверяем подключение к базе
    echo '<div class="info">📊 База данных: <strong>' . htmlspecialchars($database) . '</strong></div>';
    
    // Удаляем старые триггеры
    echo '<h2>Шаг 1: Удаление старых триггеров</h2>';
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS `check_user_activity`");
        echo '<div class="success">✅ Триггер check_user_activity удален (если существовал)</div>';
    } catch (PDOException $e) {
        echo '<div class="warning">⚠️ ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS `check_device_activity`");
        echo '<div class="success">✅ Триггер check_device_activity удален (если существовал)</div>';
    } catch (PDOException $e) {
        echo '<div class="warning">⚠️ ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    // Создаем триггер для блокировки пользователя
    echo '<h2>Шаг 2: Создание триггера для блокировки пользователя</h2>';
    
    // Создаем триггер без использования DELIMITER (через PDO)
    $trigger_user_sql = "CREATE TRIGGER `check_user_activity` 
AFTER INSERT ON `user_actions`
FOR EACH ROW
BEGIN
    DECLARE action_count INT;
    DECLARE block_until DATETIME;
    
    SELECT COUNT(*) INTO action_count
    FROM `user_actions`
    WHERE `USER_ID` = NEW.`USER_ID`
    AND `DATE_TIME` >= DATE_SUB(NOW(), INTERVAL 5 SECOND);
    
    IF action_count > 3 THEN
        SET block_until = DATE_ADD(NOW(), INTERVAL 30 SECOND);
        UPDATE `users` 
        SET `IS_BLOCKED` = 1, `BLOCKED_UNTIL` = block_until
        WHERE `USER_ID` = NEW.`USER_ID`;
    END IF;
    
    UPDATE `users`
    SET `IS_BLOCKED` = 0, `BLOCKED_UNTIL` = NULL
    WHERE `IS_BLOCKED` = 1 
    AND `BLOCKED_UNTIL` IS NOT NULL 
    AND `BLOCKED_UNTIL` < NOW();
END";
    
    // Пробуем выполнить без DELIMITER
    // PDO может не поддерживать многострочные запросы с BEGIN/END напрямую
    // Попробуем выполнить через exec с отключенной эмуляцией
    
    try {
        // Устанавливаем SQL_MODE для совместимости
        $pdo->exec("SET SQL_MODE=''");
        
        // Выполняем создание триггера
        $pdo->exec($trigger_user_sql);
        echo '<div class="success">✅ Триггер check_user_activity успешно создан!</div>';
    } catch (PDOException $e) {
        $error_msg = $e->getMessage();
        echo '<div class="error">❌ Ошибка создания триггера check_user_activity:</div>';
        echo '<pre>' . htmlspecialchars($error_msg) . '</pre>';
        
        // Если не работает через PDO, создадим инструкции для ручного выполнения
        echo '<div class="warning"><strong>💡 Решение:</strong> Скопируйте SQL-код ниже и выполните в MySQL клиенте (phpMyAdmin, MySQL Workbench, или командная строка):</div>';
        echo '<pre>';
        echo "USE `bd_lab10-2`;\n";
        echo "DELIMITER $$\n";
        echo $trigger_user_sql . "\n";
        echo "$$\n";
        echo "DELIMITER ;\n";
        echo '</pre>';
        
        // Или попробуем через временный файл и MySQL командную строку
        echo '<div class="info">🔄 Пробую альтернативный метод через командную строку MySQL...</div>';
        
        $temp_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'trigger_user_' . time() . '.sql';
        $sql_content = "USE `bd_lab10-2`;\n";
        $sql_content .= "DROP TRIGGER IF EXISTS `check_user_activity`;\n";
        $sql_content .= "DELIMITER $$\n";
        $sql_content .= $trigger_user_sql . "\n";
        $sql_content .= "$$\n";
        $sql_content .= "DELIMITER ;\n";
        
        file_put_contents($temp_file, $sql_content);
        
        // Пытаемся выполнить через командную строку
        $db_password_esc = escapeshellarg($db_password);
        $mysql_cmd = "mysql -u " . escapeshellarg($db_user);
        if (!empty($db_password)) {
            $mysql_cmd .= " -p" . $db_password_esc;
        }
        $mysql_cmd .= " " . escapeshellarg($database) . " < " . escapeshellarg($temp_file) . " 2>&1";
        
        $output = [];
        $return_var = 0;
        @exec($mysql_cmd, $output, $return_var);
        
        if ($return_var == 0 && empty($output)) {
            echo '<div class="success">✅ Триггер создан через командную строку MySQL!</div>';
        } else {
            echo '<div class="error">❌ Не удалось создать через командную строку. Попробуйте выполнить SQL вручную.</div>';
            if (!empty($output)) {
                echo '<pre>' . htmlspecialchars(implode("\n", $output)) . '</pre>';
            }
        }
        
        @unlink($temp_file);
    }
    
    // Создаем триггер для блокировки устройства
    echo '<h2>Шаг 3: Создание триггера для блокировки устройства</h2>';
    
    $trigger_device_sql = "CREATE TRIGGER `check_device_activity` 
AFTER INSERT ON `user_actions`
FOR EACH ROW
BEGIN
    DECLARE action_count INT;
    
    SELECT COUNT(*) INTO action_count
    FROM `user_actions`
    WHERE `DEVICE_ID` = NEW.`DEVICE_ID`
    AND `DATE_TIME` >= DATE_SUB(NOW(), INTERVAL 5 SECOND);
    
    IF action_count > 3 THEN
        UPDATE `device_table` 
        SET `IS_BLOCKED` = 1
        WHERE `DEVICE_ID` = NEW.`DEVICE_ID`;
    END IF;
END";
    
    try {
        $pdo->exec($trigger_device_sql);
        echo '<div class="success">✅ Триггер check_device_activity успешно создан!</div>';
    } catch (PDOException $e) {
        $error_msg = $e->getMessage();
        echo '<div class="error">❌ Ошибка создания триггера check_device_activity:</div>';
        echo '<pre>' . htmlspecialchars($error_msg) . '</pre>';
        
        echo '<div class="warning"><strong>💡 Решение:</strong> Скопируйте SQL-код ниже и выполните в MySQL клиенте:</div>';
        echo '<pre>';
        echo "USE `bd_lab10-2`;\n";
        echo "DELIMITER $$\n";
        echo $trigger_device_sql . "\n";
        echo "$$\n";
        echo "DELIMITER ;\n";
        echo '</pre>';
    }
    
    // Проверяем, что триггеры созданы
    echo '<h2>Шаг 4: Проверка созданных триггеров</h2>';
    try {
        $stmt = $pdo->query("SHOW TRIGGERS");
        $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($triggers) > 0) {
            echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
            echo '<tr style="background: #4CAF50; color: white;"><th>Триггер</th><th>Таблица</th><th>Событие</th><th>Время</th></tr>';
            foreach ($triggers as $trigger) {
                $highlight = (strpos($trigger['Trigger'], 'check_') === 0) ? ' style="background:#e8f5e9;"' : '';
                echo '<tr' . $highlight . '>';
                echo '<td><strong>' . htmlspecialchars($trigger['Trigger']) . '</strong></td>';
                echo '<td>' . htmlspecialchars($trigger['Table']) . '</td>';
                echo '<td>' . htmlspecialchars($trigger['Event']) . '</td>';
                echo '<td>' . htmlspecialchars($trigger['Timing']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
            $found_user = false;
            $found_device = false;
            foreach ($triggers as $trigger) {
                if ($trigger['Trigger'] == 'check_user_activity') $found_user = true;
                if ($trigger['Trigger'] == 'check_device_activity') $found_device = true;
            }
            
            if ($found_user && $found_device) {
                echo '<div class="success">✅ Оба триггера успешно созданы и найдены!</div>';
            } else {
                if (!$found_user) echo '<div class="error">❌ Триггер check_user_activity НЕ найден в базе данных!</div>';
                if (!$found_device) echo '<div class="error">❌ Триггер check_device_activity НЕ найден в базе данных!</div>';
            }
        } else {
            echo '<div class="error">❌ Триггеры не найдены в базе данных!</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="error">❌ Ошибка при проверке триггеров: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    // Разблокируем пользователей
    echo '<h2>Шаг 5: Разблокировка пользователей</h2>';
    try {
        $pdo->exec("UPDATE `users` SET `IS_BLOCKED` = 0, `BLOCKED_UNTIL` = NULL WHERE `IS_BLOCKED` = 1");
        $pdo->exec("UPDATE `device_table` SET `IS_BLOCKED` = 0 WHERE `IS_BLOCKED` = 1");
        echo '<div class="success">✅ Все пользователи и устройства разблокированы</div>';
    } catch (PDOException $e) {
        echo '<div class="warning">⚠️ Ошибка разблокировки: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    echo '<hr>';
    echo '<div class="info">';
    echo '<strong>📋 Следующие шаги:</strong><br><br>';
    echo '1. Обновите страницу <a href="debug_blocking.php" style="font-weight:bold;color:#2196F3;">debug_blocking.php</a> - триггеры должны быть найдены<br>';
    echo '2. Если триггеры все еще не найдены, выполните SQL-код вручную в phpMyAdmin или MySQL Workbench<br>';
    echo '3. Попробуйте протестировать блокировку на <a href="task6_index.php" style="font-weight:bold;color:#2196F3;">task6_index.php</a><br>';
    echo '</div>';
    
} catch (PDOException $e) {
    echo '<div class="error">❌ Критическая ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
} catch (Exception $e) {
    echo '<div class="error">❌ Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

echo '</body></html>';
?>
