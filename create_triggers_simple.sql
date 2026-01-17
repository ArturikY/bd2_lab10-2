-- Упрощенный SQL-скрипт для создания триггеров
-- Работает в phpMyAdmin и других MySQL клиентах
-- ВНИМАНИЕ: Выполняйте каждую секцию отдельно!

USE `bd_lab10-2`;

-- Удаляем старые триггеры
DROP TRIGGER IF EXISTS `check_user_activity`;
DROP TRIGGER IF EXISTS `check_device_activity`;

-- ========================================
-- ТРИГГЕР 1: Блокировка пользователя
-- ========================================
-- КОПИРУЙТЕ И ВЫПОЛНЯЙТЕ ВСЁ НИЖЕ ОТ DELIMITER ДО DELIMITER ;

DELIMITER $$

CREATE TRIGGER `check_user_activity` 
AFTER INSERT ON `user_actions`
FOR EACH ROW
BEGIN
    DECLARE action_count INT;
    DECLARE block_until DATETIME;
    
    SELECT COUNT(*) INTO action_count
    FROM `user_actions`
    WHERE `USER_ID` = NEW.`USER_ID`
    AND `DATE_TIME` >= DATE_SUB(NOW(), INTERVAL 5 SECOND);
    
    IF action_count > 3 THEN
        SET block_until = DATE_ADD(NOW(), INTERVAL 30 SECOND);
        UPDATE `users` 
        SET `IS_BLOCKED` = 1, `BLOCKED_UNTIL` = block_until
        WHERE `USER_ID` = NEW.`USER_ID`;
    END IF;
    
    UPDATE `users`
    SET `IS_BLOCKED` = 0, `BLOCKED_UNTIL` = NULL
    WHERE `IS_BLOCKED` = 1 
    AND `BLOCKED_UNTIL` IS NOT NULL 
    AND `BLOCKED_UNTIL` < NOW();
END$$

DELIMITER ;

-- ========================================
-- ТРИГГЕР 2: Блокировка устройства
-- ========================================
-- КОПИРУЙТЕ И ВЫПОЛНЯЙТЕ ВСЁ НИЖЕ ОТ DELIMITER ДО DELIMITER ;

DELIMITER $$

CREATE TRIGGER `check_device_activity` 
AFTER INSERT ON `user_actions`
FOR EACH ROW
BEGIN
    DECLARE action_count INT;
    
    SELECT COUNT(*) INTO action_count
    FROM `user_actions`
    WHERE `DEVICE_ID` = NEW.`DEVICE_ID`
    AND `DATE_TIME` >= DATE_SUB(NOW(), INTERVAL 5 SECOND);
    
    IF action_count > 3 THEN
        UPDATE `device_table` 
        SET `IS_BLOCKED` = 1
        WHERE `DEVICE_ID` = NEW.`DEVICE_ID`;
    END IF;
END$$

DELIMITER ;

-- Разблокируем пользователей
UPDATE `users` SET `IS_BLOCKED` = 0, `BLOCKED_UNTIL` = NULL WHERE `IS_BLOCKED` = 1;
UPDATE `device_table` SET `IS_BLOCKED` = 0 WHERE `IS_BLOCKED` = 1;

-- Проверка
SHOW TRIGGERS;

