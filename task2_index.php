<?php
// Задание 2: Подключение к БД и шаблон вынесены в отдельные файлы
require_once 'config.php';
require_once 'template.php';

$id = 1;

// Получаем из БД все данные об устройстве
$query = "SELECT DEVICE_NAME FROM DEVICE_TABLE WHERE DEVICE_ID = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $id]);
if ($stmt->rowCount() == 1) {
    $device_name = $stmt->fetchColumn();
} else {
    $device_name = '?';
}

$query = "SELECT * FROM TEMPERATURE_TABLE WHERE DEVICE_ID = :id";
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

$query = "SELECT * FROM OUT_STATE_TABLE WHERE DEVICE_ID = :id";
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
    $query = "UPDATE COMMAND_TABLE SET COMMAND='1', DATE_TIME=:date_today WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['date_today' => $date_today, 'id' => $id]);
    if ($stmt->rowCount() != 1) {
        $query = "INSERT INTO COMMAND_TABLE (DEVICE_ID, COMMAND, DATE_TIME) VALUES (:id, '1', :date_today)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id, 'date_today' => $date_today]);
    }
}

if (isset($_POST['button_off'])) {
    $date_today = date("Y-m-d H:i:s");
    $query = "UPDATE COMMAND_TABLE SET COMMAND='0', DATE_TIME=:date_today WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['date_today' => $date_today, 'id' => $id]);
    if ($stmt->rowCount() != 1) {
        $query = "INSERT INTO COMMAND_TABLE (DEVICE_ID, COMMAND, DATE_TIME) VALUES (:id, '0', :date_today)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id, 'date_today' => $date_today]);
    }
}

// Формируем интерфейс приложения для браузера
echo renderFullPage($device_name, $temperature, $temperature_dt, $out_state, $out_state_dt, $id);
?>

