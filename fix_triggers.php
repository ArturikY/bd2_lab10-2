<?php
// Комплексный скрипт для диагностики и исправления триггеров
require_once 'config.php';

echo '<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Исправление триггеров</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { background: #e8f5e9; padding: 15px; margin: 10px 0; border-left: 4px solid #4CAF50; }
        .error { background: #ffebee; padding: 15px; margin: 10px 0; border-left: 4px solid #f44336; }
        .info { background: #e3f2fd; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; }
        .warning { background: #fff3e0; padding: 15px; margin: 10px 0; border-left: 4px solid #ff9800; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; border: 1px solid #ddd; }
        button { background: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px; }
        button:hover { background: #45a049; }
        .danger { background: #f44336; }
        .danger:hover { background: #d32f2f; }
        h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 5px; }
    </style>
</head>
<body>
<h1>🔧 Диагностика и исправление триггеров</h1>';

$user_id = 1;
$device_id = 1;

try {
    // ШАГ 1: Проверяем существующие триггеры
    echo '<h2>Шаг 1: Проверка существующих триггеров</h2>';
    
    $triggers_found = [];
    try {
        $stmt = $pdo->query("SHOW TRIGGERS");
        $all_triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($all_triggers as $trigger) {
            $name = $trigger['Trigger'] ?? $trigger['TRIGGER'] ?? '';
            if (strpos(strtolower($name), 'check_') === 0) {
                $triggers_found[] = $name;
            }
        }
        
        if (count($triggers_found) > 0) {
            echo '<div class="info">Найдено триггеров: ' . implode(', ', $triggers_found) . '</div>';
        } else {
            echo '<div class="error">❌ Триггеры не найдены!</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="error">Ошибка при проверке триггеров: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    // ШАГ 2: Удаляем старые триггеры
    echo '<h2>Шаг 2: Удаление старых триггеров</h2>';
    
    if (isset($_GET['delete_triggers'])) {
        try {
            $pdo->exec("DROP TRIGGER IF EXISTS `check_user_activity`");
            $pdo->exec("DROP TRIGGER IF EXISTS `check_device_activity`");
            echo '<div class="success">✅ Старые триггеры удалены</div>';
            echo '<script>setTimeout(function(){location.reload();}, 1000);</script>';
        } catch (PDOException $e) {
            echo '<div class="error">Ошибка удаления: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        echo '<form method="GET">';
        echo '<button type="submit" name="delete_triggers" value="1" class="danger">🗑️ Удалить все триггеры</button>';
        echo '</form>';
    }
    
    // ШАГ 3: Создаем новые триггеры через прямой SQL
    echo '<h2>Шаг 3: Создание триггеров</h2>';
    
    if (isset($_GET['create_triggers'])) {
        echo '<div class="info">Создаю триггеры...</div>';
        
        // Триггер для пользователя
        try {
            $trigger_user = "
CREATE TRIGGER `check_user_activity` 
AFTER INSERT ON `user_actions`
FOR EACH ROW
BEGIN
    DECLARE action_count INT DEFAULT 0;
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
            
            // Пробуем создать через exec
            try {
                $pdo->exec($trigger_user);
                echo '<div class="success">✅ Триггер check_user_activity создан!</div>';
            } catch (PDOException $e) {
                // Если не получается через exec, создаем через временный файл
                $temp_file = tempnam(sys_get_temp_dir(), 'trigger_user_') . '.sql';
                $sql_content = "USE `bd_lab10-2`;\n";
                $sql_content .= "DROP TRIGGER IF EXISTS `check_user_activity`;\n";
                $sql_content .= "DELIMITER $$\n";
                $sql_content .= $trigger_user . "\n";
                $sql_content .= "$$\n";
                $sql_content .= "DELIMITER ;\n";
                
                file_put_contents($temp_file, $sql_content);
                
                $mysql_cmd = "mysql -u root bd_lab10-2 < " . escapeshellarg($temp_file) . " 2>&1";
                $output = [];
                $return_var = 0;
                @exec($mysql_cmd, $output, $return_var);
                
                if ($return_var == 0) {
                    echo '<div class="success">✅ Триггер check_user_activity создан через командную строку!</div>';
                } else {
                    echo '<div class="error">❌ Не удалось создать триггер автоматически</div>';
                    echo '<div class="warning"><strong>Выполните вручную в MySQL:</strong></div>';
                    echo '<pre>USE `bd_lab10-2`;' . "\n";
                    echo "DELIMITER $$\n";
                    echo $trigger_user . "\n";
                    echo "$$\n";
                    echo "DELIMITER ;</pre>";
                }
                
                @unlink($temp_file);
            }
        } catch (Exception $e) {
            echo '<div class="error">Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // Триггер для устройства
        try {
            $trigger_device = "
CREATE TRIGGER `check_device_activity` 
AFTER INSERT ON `user_actions`
FOR EACH ROW
BEGIN
    DECLARE action_count INT DEFAULT 0;
    
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
                $pdo->exec($trigger_device);
                echo '<div class="success">✅ Триггер check_device_activity создан!</div>';
            } catch (PDOException $e) {
                $temp_file = tempnam(sys_get_temp_dir(), 'trigger_device_') . '.sql';
                $sql_content = "USE `bd_lab10-2`;\n";
                $sql_content .= "DROP TRIGGER IF EXISTS `check_device_activity`;\n";
                $sql_content .= "DELIMITER $$\n";
                $sql_content .= $trigger_device . "\n";
                $sql_content .= "$$\n";
                $sql_content .= "DELIMITER ;\n";
                
                file_put_contents($temp_file, $sql_content);
                
                $mysql_cmd = "mysql -u root bd_lab10-2 < " . escapeshellarg($temp_file) . " 2>&1";
                $output = [];
                $return_var = 0;
                @exec($mysql_cmd, $output, $return_var);
                
                if ($return_var == 0) {
                    echo '<div class="success">✅ Триггер check_device_activity создан через командную строку!</div>';
                } else {
                    echo '<div class="warning"><strong>Выполните вручную в MySQL:</strong></div>';
                    echo '<pre>USE `bd_lab10-2`;' . "\n";
                    echo "DELIMITER $$\n";
                    echo $trigger_device . "\n";
                    echo "$$\n";
                    echo "DELIMITER ;</pre>";
                }
                
                @unlink($temp_file);
            }
            
            echo '<script>setTimeout(function(){location.reload();}, 2000);</script>';
            
        } catch (Exception $e) {
            echo '<div class="error">Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        echo '<form method="GET">';
        echo '<button type="submit" name="create_triggers" value="1">🔄 Создать триггеры заново</button>';
        echo '</form>';
    }
    
    // ШАГ 4: Тестирование триггеров
    echo '<h2>Шаг 4: Тестирование триггеров</h2>';
    
    if (isset($_GET['test_triggers'])) {
        require_once 'functions.php';
        
        // Разблокируем перед тестом
        $pdo->exec("UPDATE users SET IS_BLOCKED = 0, BLOCKED_UNTIL = NULL WHERE USER_ID = $user_id");
        $pdo->exec("UPDATE device_table SET IS_BLOCKED = 0 WHERE DEVICE_ID = $device_id");
        
        echo '<div class="info">🧪 Тестирую триггеры. Добавляю 5 действий подряд...</div>';
        
        $results = [];
        for ($i = 1; $i <= 5; $i++) {
            // Проверяем ДО
            $stmt = $pdo->prepare("SELECT IS_BLOCKED FROM users WHERE USER_ID = ?");
            $stmt->execute([$user_id]);
            $user_before = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT IS_BLOCKED FROM device_table WHERE DEVICE_ID = ?");
            $stmt->execute([$device_id]);
            $device_before = $stmt->fetchColumn();
            
            // Добавляем действие
            logUserAction($pdo, $user_id, $device_id, "Тест триггера $i");
            
            usleep(50000); // 50ms задержка
            
            // Проверяем ПОСЛЕ
            $stmt = $pdo->prepare("SELECT IS_BLOCKED, BLOCKED_UNTIL FROM users WHERE USER_ID = ?");
            $stmt->execute([$user_id]);
            $user_after = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("SELECT IS_BLOCKED FROM device_table WHERE DEVICE_ID = ?");
            $stmt->execute([$device_id]);
            $device_after = $stmt->fetchColumn();
            
            $results[] = [
                'action' => $i,
                'user_before' => $user_before,
                'user_after' => $user_after['IS_BLOCKED'],
                'device_before' => $device_before,
                'device_after' => $device_after
            ];
            
            echo '<div class="info">Действие #' . $i . ': пользователь=' . ($user_after['IS_BLOCKED'] ? 'ЗАБЛОКИРОВАН ✅' : 'свободен') . 
                 ', устройство=' . ($device_after ? 'ЗАБЛОКИРОВАН ✅' : 'свободен') . '</div>';
            
            if ($user_after['IS_BLOCKED'] == 1 || $device_after == 1) {
                break;
            }
        }
        
        // Итоговая проверка
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_actions WHERE USER_ID = ? AND DATE_TIME >= DATE_SUB(NOW(), INTERVAL 5 SECOND)");
        $stmt->execute([$user_id]);
        $count = $stmt->fetchColumn();
        
        echo '<hr>';
        echo '<h3>📊 Результаты:</h3>';
        echo '<div class="info">Действий за последние 5 секунд: <strong>' . $count . '</strong></div>';
        
        $final_user = $results[count($results)-1]['user_after'];
        $final_device = $results[count($results)-1]['device_after'];
        
        if ($final_user == 1) {
            echo '<div class="success">✅ Триггер блокировки пользователя РАБОТАЕТ!</div>';
        } else {
            echo '<div class="error">❌ Триггер блокировки пользователя НЕ РАБОТАЕТ! (действий: ' . $count . ')</div>';
        }
        
        if ($final_device == 1) {
            echo '<div class="success">✅ Триггер блокировки устройства РАБОТАЕТ!</div>';
        } else {
            echo '<div class="error">❌ Триггер блокировки устройства НЕ РАБОТАЕТ! (действий: ' . $count . ')</div>';
        }
        
        echo '<script>setTimeout(function(){location.reload();}, 3000);</script>';
    } else {
        echo '<form method="GET">';
        echo '<button type="submit" name="test_triggers" value="1">🧪 Протестировать триггеры</button>';
        echo '</form>';
        echo '<div class="warning">⚠️ Перед тестом убедитесь, что триггеры созданы (Шаг 3)</div>';
    }
    
    // ШАГ 5: Проверка текста триггеров
    echo '<h2>Шаг 5: Проверка текста триггеров</h2>';
    
    try {
        $stmt = $pdo->query("SHOW CREATE TRIGGER check_user_activity");
        $trigger_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trigger_info) {
            $trigger_sql = $trigger_info['SQL Original Statement'] ?? $trigger_info['Statement'] ?? '';
            if ($trigger_sql) {
                echo '<div class="info"><strong>Триггер check_user_activity:</strong></div>';
                echo '<pre>' . htmlspecialchars($trigger_sql) . '</pre>';
                
                if (strpos($trigger_sql, 'INTERVAL 5 SECOND') !== false && strpos($trigger_sql, 'action_count > 3') !== false) {
                    echo '<div class="success">✅ Триггер использует правильные параметры (5 секунд, > 3 действий)</div>';
                } else {
                    echo '<div class="error">❌ Триггер использует НЕПРАВИЛЬНЫЕ параметры!</div>';
                    if (strpos($trigger_sql, 'INTERVAL 1 MINUTE') !== false) {
                        echo '<div class="warning">⚠️ Найден старый интервал: 1 MINUTE вместо 5 SECOND</div>';
                    }
                    if (strpos($trigger_sql, 'action_count > 10') !== false) {
                        echo '<div class="warning">⚠️ Найден старый порог: > 10 вместо > 3</div>';
                    }
                }
            }
        } else {
            echo '<div class="error">❌ Триггер check_user_activity не найден</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="error">Ошибка проверки: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
} catch (PDOException $e) {
    echo '<div class="error">❌ Критическая ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

echo '<hr>';
echo '<div class="info">';
echo '<strong>📋 Инструкция:</strong><br>';
echo '1. Нажмите "Удалить все триггеры" (если нужно)<br>';
echo '2. Нажмите "Создать триггеры заново"<br>';
echo '3. Нажмите "Протестировать триггеры" для проверки<br>';
echo '4. Если триггеры не создаются автоматически, скопируйте SQL-код и выполните в MySQL вручную<br>';
echo '</div>';

echo '</body></html>';
?>

