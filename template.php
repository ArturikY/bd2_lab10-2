<?php
// Шаблон представления состояния устройства
function renderDeviceTemplate($device_name, $temperature, $temperature_dt, $out_state, $out_state_dt, $device_id = 1, $history_link = null) {
    $html = '<div class="device-block">
        <h2>Устройство: ' . htmlspecialchars($device_name) . ' (ID: ' . htmlspecialchars($device_id) . ')</h2>
        <table border="1">
            <tr>
                <td width="150px">Температура</td>
                <td width="80px">' . htmlspecialchars($temperature) . '</td>
                <td width="200px">' . htmlspecialchars($temperature_dt) . '</td>
            </tr>
            <tr>
                <td width="150px">Реле</td>
                <td width="80px">' . htmlspecialchars($out_state) . '</td>
                <td width="200px">' . htmlspecialchars($out_state_dt) . '</td>
            </tr>
        </table>
        <form method="POST">
            <input type="hidden" name="device_id" value="' . htmlspecialchars($device_id) . '">
            <button name="button_on" value="1">Включить реле</button>
        </form>
        <form method="POST">
            <input type="hidden" name="device_id" value="' . htmlspecialchars($device_id) . '">
            <button name="button_off" value="1">Выключить реле</button>
        </form>';
    
    if ($history_link) {
        $html .= '<div class="history-link"><a href="' . htmlspecialchars($history_link) . '">История управления устройством</a></div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

// Функция для получения полного HTML документа (для задания 2)
function renderFullPage($device_name, $temperature, $temperature_dt, $out_state, $out_state_dt, $device_id = 1, $history_link = null) {
    $html = '<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>MyApp</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; margin: 10px 0; }
        td { padding: 8px; }
        .device-block { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        .history-link { margin-top: 10px; }
    </style>
</head>
<body>';
    
    $html .= renderDeviceTemplate($device_name, $temperature, $temperature_dt, $out_state, $out_state_dt, $device_id, $history_link);
    
    $html .= '</body>
</html>';
    
    return $html;
}
?>

