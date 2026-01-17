<?php
// Быстрая разблокировка пользователя и устройств
require_once 'config.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1;
$device_id = isset($_GET['device_id']) ? intval($_GET['device_id']) : 0;

echo '<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Разблокировка</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 50px auto; text-align: center; }
        .success { background: #4CAF50; color: white; padding: 20px; margin: 20px 0; border-radius: 5px; font-size: 18px; }
        .info { background: #2196F3; color: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
        button { background: #4CAF50; color: white; padding: 15px 30px; border: none; cursor: pointer; font-size: 16px; border-radius: 5px; margin: 10px; }
        button:hover { background: #45a049; }
        a { color: #2196F3; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
<h1>🔓 Разблокировка</h1>';

try {
    if (isset($_GET['action']) && $_GET['action'] == 'unblock') {
        // Разблокируем пользователя
        $pdo->exec("UPDATE users SET IS_BLOCKED = 0, BLOCKED_UNTIL = NULL WHERE USER_ID = $user_id");
        
        // Разблокируем все устройства (или конкретное)
        if ($device_id > 0) {
            $pdo->exec("UPDATE device_table SET IS_BLOCKED = 0 WHERE DEVICE_ID = $device_id");
            echo '<div class="success">✅ Пользователь и устройство #' . $device_id . ' разблокированы!</div>';
        } else {
            $pdo->exec("UPDATE device_table SET IS_BLOCKED = 0");
            echo '<div class="success">✅ Все пользователи и устройства разблокированы!</div>';
        }
        
        echo '<div class="info">Через 3 секунды вы будете перенаправлены...</div>';
        echo '<script>setTimeout(function(){window.location.href="task6_index.php";}, 3000);</script>';
        
        echo '<p><a href="task6_index.php">← Вернуться к управлению устройствами</a></p>';
    } else {
        // Показываем текущий статус
        $stmt = $pdo->prepare("SELECT IS_BLOCKED, BLOCKED_UNTIL FROM users WHERE USER_ID = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['IS_BLOCKED'] == 1) {
            echo '<div class="info">🔒 Пользователь #' . $user_id . ' заблокирован</div>';
            if ($user['BLOCKED_UNTIL']) {
                echo '<div class="info">⏰ Блокировка до: ' . htmlspecialchars($user['BLOCKED_UNTIL']) . '</div>';
                
                // Проверяем, истекло ли время
                $blocked_until = strtotime($user['BLOCKED_UNTIL']);
                $now = time();
                if ($blocked_until < $now) {
                    echo '<div class="info">⏰ Время блокировки истекло, можно разблокировать</div>';
                } else {
                    $remaining = $blocked_until - $now;
                    echo '<div class="info">⏳ Осталось: ' . $remaining . ' секунд (или разблокируйте сейчас)</div>';
                }
            }
            
            echo '<p><a href="?action=unblock&user_id=' . $user_id . '"><button>🔓 Разблокировать пользователя</button></a></p>';
            echo '<p><a href="?action=unblock&user_id=' . $user_id . '&device_id=0"><button>🔓 Разблокировать всё</button></a></p>';
        } else {
            echo '<div class="success">✅ Пользователь не заблокирован</div>';
        }
        
        echo '<hr>';
        echo '<p><a href="task6_index.php">← Вернуться к управлению устройствами</a></p>';
        echo '<p><a href="test_triggers_simple.php">← К тесту триггеров</a></p>';
    }
    
} catch (PDOException $e) {
    echo '<div class="error">Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

echo '</div></body></html>';
?>

