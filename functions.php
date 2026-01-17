<?php
// Функции для работы с устройствами

function getDeviceData($pdo, $device_id) {
    $device = ['device_name' => '?', 'temperature' => '?', 'temperature_dt' => '?', 
               'out_state' => '?', 'out_state_dt' => '?'];
    
    // Получаем имя устройства (поддержка как NAME, так и DEVICE_NAME)
    $query = "SELECT COALESCE(DEVICE_NAME, NAME) AS device_name FROM device_table WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $device_id]);
    if ($stmt->rowCount() == 1) {
        $device['device_name'] = $stmt->fetchColumn();
    }
    
    // Получаем температуру
    $query = "SELECT * FROM temperature_table WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $device_id]);
    if ($stmt->rowCount() == 1) {
        $temperatureData = $stmt->fetch(PDO::FETCH_ASSOC);
        $device['temperature'] = $temperatureData['TEMPERATURE'];
        $device['temperature_dt'] = $temperatureData['DATE_TIME'];
    }
    
    // Получаем состояние реле
    $query = "SELECT * FROM out_state_table WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $device_id]);
    if ($stmt->rowCount() == 1) {
        $outStateData = $stmt->fetch(PDO::FETCH_ASSOC);
        $device['out_state'] = $outStateData['OUT_STATE'];
        $device['out_state_dt'] = $outStateData['DATE_TIME'];
    }
    
    return $device;
}

function getAllDevices($pdo) {
    $query = "SELECT DEVICE_ID FROM device_table";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function sendCommand($pdo, $device_id, $command) {
    $date_today = date("Y-m-d H:i:s");
    $query = "UPDATE command_table SET COMMAND=:command, DATE_TIME=:date_today WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['command' => $command, 'date_today' => $date_today, 'id' => $device_id]);
    if ($stmt->rowCount() != 1) {
        $query = "INSERT INTO command_table (DEVICE_ID, COMMAND, DATE_TIME) VALUES (:id, :command, :date_today)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $device_id, 'command' => $command, 'date_today' => $date_today]);
    }
}

function logUserAction($pdo, $user_id, $device_id, $action) {
    // Используем NOW() из MySQL вместо PHP date() для синхронизации с триггерами
    $query = "INSERT INTO user_actions (USER_ID, DEVICE_ID, ACTION, DATE_TIME) VALUES (:user_id, :device_id, :action, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id, 'device_id' => $device_id, 'action' => $action]);
}

function isUserBlocked($pdo, $user_id) {
    $query = "SELECT IS_BLOCKED FROM users WHERE USER_ID = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    if ($stmt->rowCount() == 1) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['IS_BLOCKED'] == 1;
    }
    return false;
}

function isDeviceBlocked($pdo, $device_id) {
    $query = "SELECT IS_BLOCKED FROM device_table WHERE DEVICE_ID = :device_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['device_id' => $device_id]);
    if ($stmt->rowCount() == 1) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($result['IS_BLOCKED']) && $result['IS_BLOCKED'] == 1;
    }
    return false;
}

function getDeviceHistory($pdo, $device_id) {
    $query = "SELECT * FROM user_actions WHERE DEVICE_ID = :device_id ORDER BY DATE_TIME DESC LIMIT 100";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['device_id' => $device_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

