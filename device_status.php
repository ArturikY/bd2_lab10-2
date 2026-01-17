<?php
// Используем общий файл конфигурации
require_once 'config.php';

// Проверка наличия параметра ID в GET-запросе
if (isset($_GET['ID'])) {
    // Валидация и санитизация входных данных
    $device_id = intval($_GET['ID']);
    if ($device_id <= 0) {
        echo "COMMAND ? EOC";
        exit;
    }
    
    // Получаем данные устройства
    $query = "SELECT * FROM device_table WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $device_id]);
    
    if ($stmt->rowCount() == 1) { // Если устройство с таким ID найдено
        // Обрабатываем состояние реле
        if (isset($_GET['Rele'])) {
            // Проверяем есть ли в БД предыдущее состояние реле
            $query = "SELECT OUT_STATE FROM out_state_table WHERE DEVICE_ID = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['id' => $device_id]);
            $date_today = date("Y-m-d H:i:s"); // текущее время
            $rele_state = intval($_GET['Rele']); // Валидация состояния реле
            
            if ($stmt->rowCount() == 1) {
                // Если данные есть, обновляем состояние реле
                $query = "UPDATE out_state_table SET OUT_STATE = :rele, DATE_TIME = :date_today WHERE DEVICE_ID = :id";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['rele' => $rele_state, 'date_today' => $date_today, 'id' => $device_id]);
            } else {
                // Если данных нет, добавляем новое состояние реле
                $query = "INSERT INTO out_state_table (DEVICE_ID, OUT_STATE, DATE_TIME) VALUES (:id, :rele, :date_today)";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['id' => $device_id, 'rele' => $rele_state, 'date_today' => $date_today]);
            }
        }

        // Обрабатываем температуру
        if (isset($_GET['Term'])) {
            // Проверяем есть ли в БД предыдущее значение температуры
            $query = "SELECT TEMPERATURE FROM temperature_table WHERE DEVICE_ID = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['id' => $device_id]);
            $date_today = date("Y-m-d H:i:s"); // текущее время
            $temperature = floatval($_GET['Term']); // Валидация температуры
            
            if ($stmt->rowCount() == 1) {
                // Если данные есть, обновляем температуру
                $query = "UPDATE temperature_table SET TEMPERATURE = :term, DATE_TIME = :date_today WHERE DEVICE_ID = :id";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['term' => $temperature, 'date_today' => $date_today, 'id' => $device_id]);
            } else {
                // Если данных нет, добавляем новую температуру
                $query = "INSERT INTO temperature_table (DEVICE_ID, TEMPERATURE, DATE_TIME) VALUES (:id, :term, :date_today)";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['id' => $device_id, 'term' => $temperature, 'date_today' => $date_today]);
            }
        }

        // Достаём из БД текущую команду управления реле
        $query = "SELECT COMMAND FROM command_table WHERE DEVICE_ID = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $device_id]);
        
        if ($stmt->rowCount() == 1) {
            // Если команда найдена, извлекаем её
            $arr = $stmt->fetch(PDO::FETCH_ASSOC);
            $command = $arr['COMMAND'];
        }

        // Отправляем команду
        if (isset($command) && $command != -1) { // Если команда найдена
            echo "COMMAND $command EOC";
        } else {
            echo "COMMAND ? EOC";
        }
    }
}
?>