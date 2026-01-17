<?php

//—————————Настройки подключения к БД————————
$db_host = 'localhost';
$db_user = 'root'; //имя пользователя совпадает с именем БД
$db_password = ''; //пароль, указанный при создании БД
$database = 'bd_lab10-2'; //имя БД, которое было указано при создании

// Подключение к базе данных через PDO
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$database", $db_user, $db_password);
    // Устанавливаем атрибуты для обработки ошибок
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo 'Подключение к базе данных успешно!<br>';
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

//—————————————————————————————-
$id = 1;

//——————Получаем из БД все данные об устройстве——————-
$query = "SELECT * FROM DEVICE_TABLE WHERE DEVICE_ID = :id";
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
//—————————————————————————————-

//——Проверяем данные, полученные от пользователя———————
if (isset($_POST['button_on'])) {
    $date_today = date("Y-m-d H:i:s");
    $query = "UPDATE COMMAND_TABLE SET COMMAND='1', DATE_TIME=:date_today WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['date_today' => $date_today, 'id' => $id]);
    if ($stmt->rowCount() != 1) {
        // Если не смогли обновить — вставляем в таблицу строчку с данными о команде для устройства
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
        // Если не смогли обновить — вставляем в таблицу строчку с данными о команде для устройства
        $query = "INSERT INTO COMMAND_TABLE (DEVICE_ID, COMMAND, DATE_TIME) VALUES (:id, '0', :date_today)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id, 'date_today' => $date_today]);
    }
}
//————————————————————————

//——-Формируем интерфейс приложения для браузера———————
echo '
<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>MyApp</title>
</head>
<body>
<table>
<tr>
<td width=100px> Устройство:</td>
<td width=40px>'.$device_name.'</td>
</tr>
</table>

<table border=1>
<tr>
<td width=100px> Температура</td>
<td width=40px>'.$temperature.'</td>
<td width=150px>'.$temperature_dt.'</td>
</tr>
<tr>
<td width=100px> Реле</td>
<td width=40px>'.$out_state.'</td>
<td width=150px>'.$out_state_dt.'</td>
</tr>
</table>

<form method="POST">
    <button name="button_on" value="1">Включить реле</button>
</form>
<form method="POST">
    <button name="button_off" value="1">Выключить реле</button>
</form>

</body>
</html>';

?>