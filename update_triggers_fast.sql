-- Обновление триггеров для быстрого тестирования блокировки
-- Выполните этот скрипт, чтобы изменить время проверки с 1 минуты на 5 секунд
USE `bd_lab10-2`;

-- Удаляем старые триггеры
DROP TRIGGER IF EXISTS `check_user_activity`;
DROP TRIGGER IF EXISTS `check_device_activity`;

-- Триггер для блокировки пользователя при частых обращениях (Задание 6)
-- ИЗМЕНЕНО: 5 секунд вместо 1 минуты, 3 действия вместо 10
DELIMITER $$

CREATE TRIGGER `check_user_activity` 
AFTER INSERT ON `user_actions`
FOR EACH ROW
BEGIN
    DECLARE action_count INT;
    DECLARE block_until DATETIME;
    
    -- Подсчитываем количество действий пользователя за последние 5 секунд
    SELECT COUNT(*) INTO action_count
    FROM `user_actions`
    WHERE `USER_ID` = NEW.`USER_ID`
    AND `DATE_TIME` >= DATE_SUB(NOW(), INTERVAL 5 SECOND);
    
    -- Если больше 3 действий за 5 секунд - блокируем на 30 секунд
    IF action_count > 3 THEN
        SET block_until = DATE_ADD(NOW(), INTERVAL 30 SECOND);
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
-- ИЗМЕНЕНО: 5 секунд вместо 30 секунд, 3 обращения вместо 5
DELIMITER $$

CREATE TRIGGER `check_device_activity` 
AFTER INSERT ON `user_actions`
FOR EACH ROW
BEGIN
    DECLARE action_count INT;
    
    -- Подсчитываем количество обращений к устройству за последние 5 секунд
    SELECT COUNT(*) INTO action_count
    FROM `user_actions`
    WHERE `DEVICE_ID` = NEW.`DEVICE_ID`
    AND `DATE_TIME` >= DATE_SUB(NOW(), INTERVAL 5 SECOND);
    
    -- Если больше 3 обращений за 5 секунд - блокируем устройство
    IF action_count > 3 THEN
        UPDATE `device_table` 
        SET `IS_BLOCKED` = 1
        WHERE `DEVICE_ID` = NEW.`DEVICE_ID`;
    END IF;
END$$

DELIMITER ;

-- Разблокируем всех пользователей перед тестированием (опционально)
UPDATE `users` SET `IS_BLOCKED` = 0, `BLOCKED_UNTIL` = NULL WHERE `IS_BLOCKED` = 1;
UPDATE `device_table` SET `IS_BLOCKED` = 0 WHERE `IS_BLOCKED` = 1;

-- Проверка, что триггеры созданы
SHOW TRIGGERS;

