# Инструкция по обновлению базы данных

## Важно!

У вас уже есть база данных `bd_lab10-2` с таблицей `device_table`. 

Для работы всех 8 заданий нужно выполнить SQL-скрипт для добавления недостающих таблиц и полей:

```bash
mysql -u root -p bd_lab10-2 < database_schema_update.sql
```

Или через phpMyAdmin: импортируйте файл `database_schema_update.sql`

## Что делает скрипт:

1. **Добавляет поля в `device_table`:**
   - `DEVICE_NAME` - имя устройства (для совместимости)
   - `DEVICE_TOKEN` - токен для идентификации устройства (задание 4)
   - `IS_BLOCKED` - флаг блокировки устройства (задание 7)

2. **Создает новые таблицы:**
   - `temperature_table` - хранение температуры устройств
   - `out_state_table` - состояние реле устройств
   - `command_table` - команды управления устройствами
   - `users` - пользователи системы (для заданий 5-8)
   - `user_actions` - история действий пользователей (задание 5)

3. **Создает триггеры:**
   - `check_user_activity` - блокировка пользователя при частых обращениях (задание 6)
   - `check_device_activity` - блокировка устройства при частых обращениях (задание 7)

4. **Добавляет тестовые данные:**
   - Пользователь admin (ID: 1)
   - Токены для существующих устройств
   - Дополнительные устройства для тестирования (если их нет)

## Структура после обновления:

### device_table (существующая + новые поля):
- `DEVICE_ID` (существующее)
- `DEVICE_LOGIN` (существующее)
- `DEVICE_PASSWORD` (существующее)
- `NAME` (существующее)
- `DEVICE_NAME` (новое, копируется из NAME)
- `DEVICE_TOKEN` (новое)
- `IS_BLOCKED` (новое)

### Новые таблицы:
- `temperature_table` - DEVICE_ID, TEMPERATURE, DATE_TIME
- `out_state_table` - DEVICE_ID, OUT_STATE, DATE_TIME
- `command_table` - DEVICE_ID, COMMAND, DATE_TIME
- `users` - USER_ID, USERNAME, IS_BLOCKED, BLOCKED_UNTIL
- `user_actions` - ACTION_ID, USER_ID, DEVICE_ID, ACTION, DATE_TIME

## Проверка после обновления:

Выполните в MySQL:
```sql
SHOW TABLES;
DESCRIBE device_table;
SHOW TRIGGERS;
```

Должны появиться все новые таблицы, поля в device_table и триггеры.

