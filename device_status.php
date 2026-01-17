<?php
// Настройки подключения к БД
$db_host = 'localhost';
$db_user = 'root'; // имя пользователя
$db_password = ''; // пароль (пусто, если нет пароля)
$database = 'bd_lab10-2'; // имя БД

// Подключение к БД через PDO
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$database", $db_user, $db_password);
    // Устанавливаем атрибуты для обработки ошибок
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Проверка наличия параметра ID в GET-запросе
if (isset($_GET['ID'])) {
    // Получаем данные устройства
    $query = "SELECT * FROM DEVICE_TABLE WHERE DEVICE_ID = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $_GET['ID']]);
    
    if ($stmt->rowCount() == 1) { // Если устройство с таким ID найдено
        // Обрабатываем состояние реле
        if (isset($_GET['Rele'])) {
            // Проверяем есть ли в БД предыдущее состояние реле
            $query = "SELECT OUT_STATE FROM OUT_STATE_TABLE WHERE DEVICE_ID = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['id' => $_GET['ID']]);
            $date_today = date("Y-m-d H:i:s"); // текущее время
            
            if ($stmt->rowCount() == 1) {
                // Если данные есть, обновляем состояние реле
                $query = "UPDATE OUT_STATE_TABLE SET OUT_STATE = :rele, DATE_TIME = :date_today WHERE DEVICE_ID = :id";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['rele' => $_GET['Rele'], 'date_today' => $date_today, 'id' => $_GET['ID']]);
            } else {
                // Если данных нет, добавляем новое состояние реле
                $query = "INSERT INTO OUT_STATE_TABLE (DEVICE_ID, OUT_STATE, DATE_TIME) VALUES (:id, :rele, :date_today)";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['id' => $_GET['ID'], 'rele' => $_GET['Rele'], 'date_today' => $date_today]);
            }
        }

        // Обрабатываем температуру
        if (isset($_GET['Term'])) {
            // Проверяем есть ли в БД предыдущее значение температуры
            $query = "SELECT TEMPERATURE FROM TEMPERATURE_TABLE WHERE DEVICE_ID = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['id' => $_GET['ID']]);
            $date_today = date("Y-m-d H:i:s"); // текущее время
            
            if ($stmt->rowCount() == 1) {
                // Если данные есть, обновляем температуру
                $query = "UPDATE TEMPERATURE_TABLE SET TEMPERATURE = :term, DATE_TIME = :date_today WHERE DEVICE_ID = :id";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['term' => $_GET['Term'], 'date_today' => $date_today, 'id' => $_GET['ID']]);
            } else {
                // Если данных нет, добавляем новую температуру
                $query = "INSERT INTO TEMPERATURE_TABLE (DEVICE_ID, TEMPERATURE, DATE_TIME) VALUES (:id, :term, :date_today)";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['id' => $_GET['ID'], 'term' => $_GET['Term'], 'date_today' => $date_today]);
            }
        }

        // Достаём из БД текущую команду управления реле
        $query = "SELECT COMMAND FROM COMMAND_TABLE WHERE DEVICE_ID = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $_GET['ID']]);
        
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