<?php
// Страница истории управления устройством
require_once 'config.php';
require_once 'functions.php';

$device_id = isset($_GET['device_id']) ? intval($_GET['device_id']) : 1;

// Получаем имя устройства
$query = "SELECT DEVICE_NAME FROM DEVICE_TABLE WHERE DEVICE_ID = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $device_id]);
$device_name = $stmt->rowCount() == 1 ? $stmt->fetchColumn() : 'Устройство #' . $device_id;

// Получаем историю действий
$history = getDeviceHistory($pdo, $device_id);

echo '<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>История управления - ' . htmlspecialchars($device_name) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        a { color: #4CAF50; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>История управления устройством: ' . htmlspecialchars($device_name) . '</h1>
    <p><a href="task5_index.php">← Вернуться к списку устройств</a></p>
    <table>
        <tr>
            <th>Дата и время</th>
            <th>Пользователь</th>
            <th>Действие</th>
        </tr>';

if (count($history) > 0) {
    foreach ($history as $action) {
        echo '<tr>
            <td>' . htmlspecialchars($action['DATE_TIME']) . '</td>
            <td>Пользователь #' . htmlspecialchars($action['USER_ID']) . '</td>
            <td>' . htmlspecialchars($action['ACTION']) . '</td>
        </tr>';
    }
} else {
    echo '<tr><td colspan="3">История действий пуста</td></tr>';
}

echo '</table>
</body>
</html>';
?>

