-- ФИНАЛЬНОЕ ИСПРАВЛЕНИЕ ТРИГГЕРОВ
-- ВАЖНО: Выполняйте в phpMyAdmin по одному блоку (от DELIMITER $$ до DELIMITER ;)

USE `bd_lab10-2`;

-- ============================================
-- БЛОК 1: Удаляем старые триггеры
-- ============================================
DROP TRIGGER IF EXISTS `check_user_activity`;
DROP TRIGGER IF EXISTS `check_device_activity`;

-- ============================================
-- БЛОК 2: Триггер для блокировки пользователя
-- Скопируйте ВСЁ от DELIMITER $$ до DELIMITER ; и выполните целиком
-- ============================================
DELIMITER $$

CREATE TRIGGER `check_user_activity` 
AFTER INSERT ON `user_actions`
FOR EACH ROW
BEGIN
    DECLARE action_count INT DEFAULT 0;
    DECLARE block_until DATETIME;
    
    -- Считаем действия за последние 5 секунд
    SELECT COUNT(*) INTO action_count
    FROM `user_actions`
    WHERE `USER_ID` = NEW.`USER_ID`
    AND `DATE_TIME` >= DATE_SUB(NOW(), INTERVAL 5 SECOND);
    
    -- Если больше 3 действий - блокируем
    IF action_count > 3 THEN
        SET block_until = DATE_ADD(NOW(), INTERVAL 30 SECOND);
        UPDATE `users` 
        SET `IS_BLOCKED` = 1, `BLOCKED_UNTIL` = block_until
        WHERE `USER_ID` = NEW.`USER_ID`;
    END IF;
    
    -- Автоматическая разблокировка
    UPDATE `users`
    SET `IS_BLOCKED` = 0, `BLOCKED_UNTIL` = NULL
    WHERE `IS_BLOCKED` = 1 
    AND `BLOCKED_UNTIL` IS NOT NULL 
    AND `BLOCKED_UNTIL` < NOW();
END$$

DELIMITER ;

-- ============================================
-- БЛОК 3: Триггер для блокировки устройства
-- Скопируйте ВСЁ от DELIMITER $$ до DELIMITER ; и выполните целиком
-- ============================================
DELIMITER $$

CREATE TRIGGER `check_device_activity` 
AFTER INSERT ON `user_actions`
FOR EACH ROW
BEGIN
    DECLARE action_count INT DEFAULT 0;
    
    -- Считаем обращения к устройству за последние 5 секунд
    SELECT COUNT(*) INTO action_count
    FROM `user_actions`
    WHERE `DEVICE_ID` = NEW.`DEVICE_ID`
    AND `DATE_TIME` >= DATE_SUB(NOW(), INTERVAL 5 SECOND);
    
    -- Если больше 3 обращений - блокируем устройство
    IF action_count > 3 THEN
        UPDATE `device_table` 
        SET `IS_BLOCKED` = 1
        WHERE `DEVICE_ID` = NEW.`DEVICE_ID`;
    END IF;
END$$

DELIMITER ;

-- ============================================
-- Проверка: должны быть 2 триггера
-- ============================================
SHOW TRIGGERS;

