-- Схема базы данных для лабораторной работы 10
-- Создание базы данных
CREATE DATABASE IF NOT EXISTS `bd_lab10-2` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bd_lab10-2`;

-- Таблица устройств (Задание 1-3)
CREATE TABLE IF NOT EXISTS `DEVICE_TABLE` (
    `DEVICE_ID` INT PRIMARY KEY AUTO_INCREMENT,
    `DEVICE_NAME` VARCHAR(255) NOT NULL,
    `DEVICE_TOKEN` VARCHAR(255) UNIQUE, -- Для задания 4
    `IS_BLOCKED` TINYINT(1) DEFAULT 0 -- Для задания 7
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица температуры
CREATE TABLE IF NOT EXISTS `TEMPERATURE_TABLE` (
    `DEVICE_ID` INT NOT NULL,
    `TEMPERATURE` DECIMAL(5,2),
    `DATE_TIME` DATETIME,
    PRIMARY KEY (`DEVICE_ID`),
    FOREIGN KEY (`DEVICE_ID`) REFERENCES `DEVICE_TABLE`(`DEVICE_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица состояния реле
CREATE TABLE IF NOT EXISTS `OUT_STATE_TABLE` (
    `DEVICE_ID` INT NOT NULL,
    `OUT_STATE` INT,
    `DATE_TIME` DATETIME,
    PRIMARY KEY (`DEVICE_ID`),
    FOREIGN KEY (`DEVICE_ID`) REFERENCES `DEVICE_TABLE`(`DEVICE_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица команд управления
CREATE TABLE IF NOT EXISTS `COMMAND_TABLE` (
    `DEVICE_ID` INT NOT NULL,
    `COMMAND` INT,
    `DATE_TIME` DATETIME,
    PRIMARY KEY (`DEVICE_ID`),
    FOREIGN KEY (`DEVICE_ID`) REFERENCES `DEVICE_TABLE`(`DEVICE_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица пользователей (для заданий 5-8)
CREATE TABLE IF NOT EXISTS `USERS` (
    `USER_ID` INT PRIMARY KEY AUTO_INCREMENT,
    `USERNAME` VARCHAR(255) NOT NULL,
    `IS_BLOCKED` TINYINT(1) DEFAULT 0,
    `BLOCKED_UNTIL` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица действий пользователей (для задания 5)
CREATE TABLE IF NOT EXISTS `USER_ACTIONS` (
    `ACTION_ID` INT PRIMARY KEY AUTO_INCREMENT,
    `USER_ID` INT NOT NULL,
    `DEVICE_ID` INT NOT NULL,
    `ACTION` VARCHAR(255) NOT NULL,
    `DATE_TIME` DATETIME NOT NULL,
    FOREIGN KEY (`USER_ID`) REFERENCES `USERS`(`USER_ID`) ON DELETE CASCADE,
    FOREIGN KEY (`DEVICE_ID`) REFERENCES `DEVICE_TABLE`(`DEVICE_ID`) ON DELETE CASCADE,
    INDEX `idx_user_device_time` (`USER_ID`, `DEVICE_ID`, `DATE_TIME`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка тестовых данных
INSERT INTO `USERS` (`USER_ID`, `USERNAME`) VALUES (1, 'admin');

INSERT INTO `DEVICE_TABLE` (`DEVICE_ID`, `DEVICE_NAME`, `DEVICE_TOKEN`) VALUES 
(1, 'Устройство 1', 'token_device_1'),
(2, 'Устройство 2', 'token_device_2'),
(3, 'Устройство 3', 'token_device_3');

-- Триггер для блокировки пользователя при частых обращениях (Задание 6)
DELIMITER $$

CREATE TRIGGER `check_user_activity` 
AFTER INSERT ON `USER_ACTIONS`
FOR EACH ROW
BEGIN
    DECLARE action_count INT;
    DECLARE block_until DATETIME;
    
    -- Подсчитываем количество действий пользователя за последнюю минуту
    SELECT COUNT(*) INTO action_count
    FROM `USER_ACTIONS`
    WHERE `USER_ID` = NEW.`USER_ID`
    AND `DATE_TIME` >= DATE_SUB(NOW(), INTERVAL 1 MINUTE);
    
    -- Если больше 10 действий за минуту - блокируем на 5 минут
    IF action_count > 10 THEN
        SET block_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE);
        UPDATE `USERS` 
        SET `IS_BLOCKED` = 1, `BLOCKED_UNTIL` = block_until
        WHERE `USER_ID` = NEW.`USER_ID`;
    END IF;
    
    -- Автоматическая разблокировка после истечения времени
    UPDATE `USERS`
    SET `IS_BLOCKED` = 0, `BLOCKED_UNTIL` = NULL
    WHERE `IS_BLOCKED` = 1 
    AND `BLOCKED_UNTIL` IS NOT NULL 
    AND `BLOCKED_UNTIL` < NOW();
END$$

DELIMITER ;

-- Триггер для блокировки устройства при частых обращениях (Задание 7)
DELIMITER $$

CREATE TRIGGER `check_device_activity` 
AFTER INSERT ON `USER_ACTIONS`
FOR EACH ROW
BEGIN
    DECLARE action_count INT;
    
    -- Подсчитываем количество обращений к устройству за последние 30 секунд
    SELECT COUNT(*) INTO action_count
    FROM `USER_ACTIONS`
    WHERE `DEVICE_ID` = NEW.`DEVICE_ID`
    AND `DATE_TIME` >= DATE_SUB(NOW(), INTERVAL 30 SECOND);
    
    -- Если больше 5 обращений за 30 секунд - блокируем устройство
    IF action_count > 5 THEN
        UPDATE `DEVICE_TABLE` 
        SET `IS_BLOCKED` = 1
        WHERE `DEVICE_ID` = NEW.`DEVICE_ID`;
    END IF;
END$$

DELIMITER ;

