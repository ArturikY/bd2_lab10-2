-- Схема базы данных для лабораторной работы 10
-- Обновление существующей структуры БД (совместимо с MariaDB)
-- ВНИМАНИЕ: Этот скрипт добавляет недостающие поля и таблицы к существующей БД

USE `bd_lab10-2`;

-- Проверяем и добавляем недостающие поля в device_table (с проверкой существования)
SET @dbname = DATABASE();
SET @tablename = 'device_table';
SET @columnname = 'DEVICE_NAME';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` CHAR(40) DEFAULT NULL AFTER `NAME`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'DEVICE_TOKEN';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` VARCHAR(255) DEFAULT NULL AFTER `DEVICE_PASSWORD`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'IS_BLOCKED';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname, '` TINYINT(1) DEFAULT 0 AFTER `DEVICE_TOKEN`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Копируем данные из NAME в DEVICE_NAME для совместимости (если DEVICE_NAME пустое)
UPDATE `device_table` SET `DEVICE_NAME` = `NAME` WHERE `DEVICE_NAME` IS NULL OR `DEVICE_NAME` = '';

-- Создаем недостающие таблицы (используем IF NOT EXISTS - работает в MariaDB)
CREATE TABLE IF NOT EXISTS `temperature_table` (
    `DEVICE_ID` INT NOT NULL,
    `TEMPERATURE` DECIMAL(5,2),
    `DATE_TIME` DATETIME,
    PRIMARY KEY (`DEVICE_ID`),
    FOREIGN KEY (`DEVICE_ID`) REFERENCES `device_table`(`DEVICE_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `out_state_table` (
    `DEVICE_ID` INT NOT NULL,
    `OUT_STATE` INT,
    `DATE_TIME` DATETIME,
    PRIMARY KEY (`DEVICE_ID`),
    FOREIGN KEY (`DEVICE_ID`) REFERENCES `device_table`(`DEVICE_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `command_table` (
    `DEVICE_ID` INT NOT NULL,
    `COMMAND` INT,
    `DATE_TIME` DATETIME,
    PRIMARY KEY (`DEVICE_ID`),
    FOREIGN KEY (`DEVICE_ID`) REFERENCES `device_table`(`DEVICE_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Таблица пользователей (для заданий 5-8)
CREATE TABLE IF NOT EXISTS `users` (
    `USER_ID` INT PRIMARY KEY AUTO_INCREMENT,
    `USERNAME` VARCHAR(255) NOT NULL,
    `IS_BLOCKED` TINYINT(1) DEFAULT 0,
    `BLOCKED_UNTIL` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Таблица действий пользователей (для задания 5)
CREATE TABLE IF NOT EXISTS `user_actions` (
    `ACTION_ID` INT PRIMARY KEY AUTO_INCREMENT,
    `USER_ID` INT NOT NULL,
    `DEVICE_ID` INT NOT NULL,
    `ACTION` VARCHAR(255) NOT NULL,
    `DATE_TIME` DATETIME NOT NULL,
    FOREIGN KEY (`USER_ID`) REFERENCES `users`(`USER_ID`) ON DELETE CASCADE,
    FOREIGN KEY (`DEVICE_ID`) REFERENCES `device_table`(`DEVICE_ID`) ON DELETE CASCADE,
    INDEX `idx_user_device_time` (`USER_ID`, `DEVICE_ID`, `DATE_TIME`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Вставка тестовых данных (если их еще нет)
INSERT IGNORE INTO `users` (`USER_ID`, `USERNAME`) VALUES (1, 'admin');

-- Если нет DEVICE_TOKEN для устройства, добавляем токен
UPDATE `device_table` SET `DEVICE_TOKEN` = CONCAT('token_device_', `DEVICE_ID`) 
WHERE `DEVICE_TOKEN` IS NULL OR `DEVICE_TOKEN` = '';

-- Создаем дополнительные устройства для тестирования (только если их нет)
INSERT IGNORE INTO `device_table` (`DEVICE_ID`, `DEVICE_LOGIN`, `DEVICE_PASSWORD`, `NAME`, `DEVICE_NAME`, `DEVICE_TOKEN`) VALUES 
(2, '2222', '3333', 'Device 2', 'Device 2', 'token_device_2'),
(3, '3333', '4444', 'Device 3', 'Device 3', 'token_device_3');

-- Удаляем старые триггеры, если они существуют (для пересоздания)
DROP TRIGGER IF EXISTS `check_user_activity`;
DROP TRIGGER IF EXISTS `check_device_activity`;

-- Триггер для блокировки пользователя при частых обращениях (Задание 6)
DELIMITER $$

CREATE TRIGGER `check_user_activity` 
AFTER INSERT ON `user_actions`
FOR EACH ROW
BEGIN
    DECLARE action_count INT;
    DECLARE block_until DATETIME;
    
    -- Подсчитываем количество действий пользователя за последнюю минуту
    SELECT COUNT(*) INTO action_count
    FROM `user_actions`
    WHERE `USER_ID` = NEW.`USER_ID`
    AND `DATE_TIME` >= DATE_SUB(NOW(), INTERVAL 1 MINUTE);
    
    -- Если больше 10 действий за минуту - блокируем на 5 минут
    IF action_count > 10 THEN
        SET block_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE);
        UPDATE `users` 
        SET `IS_BLOCKED` = 1, `BLOCKED_UNTIL` = block_until
        WHERE `USER_ID` = NEW.`USER_ID`;
    END IF;
    
    -- Автоматическая разблокировка после истечения времени
    UPDATE `users`
    SET `IS_BLOCKED` = 0, `BLOCKED_UNTIL` = NULL
    WHERE `IS_BLOCKED` = 1 
    AND `BLOCKED_UNTIL` IS NOT NULL 
    AND `BLOCKED_UNTIL` < NOW();
END$$

DELIMITER ;

-- Триггер для блокировки устройства при частых обращениях (Задание 7)
DELIMITER $$

CREATE TRIGGER `check_device_activity` 
AFTER INSERT ON `user_actions`
FOR EACH ROW
BEGIN
    DECLARE action_count INT;
    
    -- Подсчитываем количество обращений к устройству за последние 30 секунд
    SELECT COUNT(*) INTO action_count
    FROM `user_actions`
    WHERE `DEVICE_ID` = NEW.`DEVICE_ID`
    AND `DATE_TIME` >= DATE_SUB(NOW(), INTERVAL 30 SECOND);
    
    -- Если больше 5 обращений за 30 секунд - блокируем устройство
    IF action_count > 5 THEN
        UPDATE `device_table` 
        SET `IS_BLOCKED` = 1
        WHERE `DEVICE_ID` = NEW.`DEVICE_ID`;
    END IF;
END$$

DELIMITER ;

