<?php
// Задание 2: Подключение к БД и шаблон вынесены в отдельные файлы
require_once 'config.php';
require_once 'template.php';

$id = 1;

// Получаем из БД все данные об устройстве (поддержка как NAME, так и DEVICE_NAME)
$query = "SELECT COALESCE(DEVICE_NAME, NAME) AS device_name FROM device_table WHERE DEVICE_ID = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $id]);
if ($stmt->rowCount() == 1) {
    $device_name = $stmt->fetchColumn();
} else {
    $device_name = '?';
}

$query = "SELECT * FROM temperature_table WHERE DEVICE_ID = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $id]);
if ($stmt->rowCount() == 1) {
    $temperatureData = $stmt->fetch(PDO::FETCH_ASSOC);
    $temperature = $temperatureData['TEMPERATURE'];
    $temperature_dt = $temperatureData['DATE_TIME'];
} else {
    $temperature = '?';
    $temperature_dt = '?';
}

$query = "SELECT * FROM out_state_table WHERE DEVICE_ID = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $id]);
if ($stmt->rowCount() == 1) {
    $outStateData = $stmt->fetch(PDO::FETCH_ASSOC);
    $out_state = $outStateData['OUT_STATE'];
    $out_state_dt = $outStateData['DATE_TIME'];
} else {
    $out_state = '?';
    $out_state_dt = '?';
}

// Проверяем данные, полученные от пользователя
if (isset($_POST['button_on'])) {
    $date_today = date("Y-m-d H:i:s");
    // Сохраняем команду
    $query = "UPDATE command_table SET COMMAND='1', DATE_TIME=:date_today WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['date_today' => $date_today, 'id' => $id]);
    if ($stmt->rowCount() != 1) {
        $query = "INSERT INTO command_table (DEVICE_ID, COMMAND, DATE_TIME) VALUES (:id, '1', :date_today)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id, 'date_today' => $date_today]);
    }
    
    // Сразу обновляем состояние реле для немедленного отображения
    $query = "UPDATE out_state_table SET OUT_STATE=1, DATE_TIME=:date_today WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['date_today' => $date_today, 'id' => $id]);
    if ($stmt->rowCount() != 1) {
        $query = "INSERT INTO out_state_table (DEVICE_ID, OUT_STATE, DATE_TIME) VALUES (:id, 1, :date_today)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id, 'date_today' => $date_today]);
    }
    // Перезагружаем данные из БД для корректного отображения
    $query = "SELECT * FROM out_state_table WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);
    if ($stmt->rowCount() == 1) {
        $outStateData = $stmt->fetch(PDO::FETCH_ASSOC);
        $out_state = $outStateData['OUT_STATE'];
        $out_state_dt = $outStateData['DATE_TIME'];
    }
}

if (isset($_POST['button_off'])) {
    $date_today = date("Y-m-d H:i:s");
    // Сохраняем команду
    $query = "UPDATE command_table SET COMMAND='0', DATE_TIME=:date_today WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['date_today' => $date_today, 'id' => $id]);
    if ($stmt->rowCount() != 1) {
        $query = "INSERT INTO command_table (DEVICE_ID, COMMAND, DATE_TIME) VALUES (:id, '0', :date_today)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id, 'date_today' => $date_today]);
    }
    
    // Сразу обновляем состояние реле для немедленного отображения
    $query = "UPDATE out_state_table SET OUT_STATE=0, DATE_TIME=:date_today WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['date_today' => $date_today, 'id' => $id]);
    if ($stmt->rowCount() != 1) {
        $query = "INSERT INTO out_state_table (DEVICE_ID, OUT_STATE, DATE_TIME) VALUES (:id, 0, :date_today)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id, 'date_today' => $date_today]);
    }
    // Перезагружаем данные из БД для корректного отображения
    $query = "SELECT * FROM out_state_table WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);
    if ($stmt->rowCount() == 1) {
        $outStateData = $stmt->fetch(PDO::FETCH_ASSOC);
        $out_state = $outStateData['OUT_STATE'];
        $out_state_dt = $outStateData['DATE_TIME'];
    }
}

// Формируем интерфейс приложения для браузера
echo renderFullPage($device_name, $temperature, $temperature_dt, $out_state, $out_state_dt, $id);
?>

