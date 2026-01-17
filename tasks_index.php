<?php
// Индексная страница для доступа ко всем заданиям
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Лабораторная работа 10 - Все задания</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 40px; 
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { 
            color: #333; 
            text-align: center;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .task-list {
            list-style: none;
            padding: 0;
        }
        .task-item {
            margin: 15px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #4CAF50;
            border-radius: 5px;
        }
        .task-item h3 {
            margin: 0 0 10px 0;
            color: #4CAF50;
        }
        .task-item p {
            margin: 5px 0;
            color: #666;
        }
        .task-link {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .task-link:hover {
            background-color: #45a049;
        }
        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-completed {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Лабораторная работа 10</h1>
        <h2 style="text-align: center; color: #666;">Веб-приложение для дистанционного управления температурой устройства</h2>
        
        <ul class="task-list">
            <li class="task-item">
                <h3>Задание 1 [1/8] <span class="status status-completed">✓ Выполнено</span></h3>
                <p><strong>Описание:</strong> Базовое веб-приложение для управления одним устройством (ID = 1)</p>
                <p><strong>Функционал:</strong> Отображение температуры и состояния реле, кнопки управления</p>
                <a href="index.php" class="task-link">Открыть задание 1</a>
            </li>
            
            <li class="task-item">
                <h3>Задание 2 [1/8] <span class="status status-completed">✓ Выполнено</span></h3>
                <p><strong>Описание:</strong> Подключение к БД и шаблон представления вынесены в отдельные файлы</p>
                <p><strong>Функционал:</strong> config.php, template.php, функции для работы с БД</p>
                <a href="task2_index.php" class="task-link">Открыть задание 2</a>
            </li>
            
            <li class="task-item">
                <h3>Задание 3 [1/8] <span class="status status-completed">✓ Выполнено</span></h3>
                <p><strong>Описание:</strong> Поддержка нескольких устройств на одной странице</p>
                <p><strong>Функционал:</strong> Отображение всех устройств, управление каждым устройством</p>
                <a href="task3_index.php" class="task-link">Открыть задание 3</a>
            </li>
            
            <li class="task-item">
                <h3>Задание 4 [1/8] <span class="status status-completed">✓ Выполнено</span></h3>
                <p><strong>Описание:</strong> Усовершенствованный механизм идентификации устройства</p>
                <p><strong>Функционал:</strong> Идентификация по токену, использование сессий</p>
                <a href="task4_index.php?device_token=token_device_1" class="task-link">Открыть задание 4</a>
            </li>
            
            <li class="task-item">
                <h3>Задание 5 [1/8] <span class="status status-completed">✓ Выполнено</span></h3>
                <p><strong>Описание:</strong> Учет действий пользователя и история управления</p>
                <p><strong>Функционал:</strong> Таблица USER_ACTIONS, страница истории для каждого устройства</p>
                <a href="task5_index.php" class="task-link">Открыть задание 5</a>
            </li>
            
            <li class="task-item">
                <h3>Задание 6 [1/8] <span class="status status-completed">✓ Выполнено</span></h3>
                <p><strong>Описание:</strong> Триггер блокировки пользователя при частых обращениях</p>
                <p><strong>Функционал:</strong> Блокировка при >10 действиях/минуту, автоматическая разблокировка</p>
                <a href="task6_index.php" class="task-link">Открыть задание 6</a>
            </li>
            
            <li class="task-item">
                <h3>Задание 7 [1/8] <span class="status status-completed">✓ Выполнено</span></h3>
                <p><strong>Описание:</strong> Триггер блокировки устройства при частых обращениях</p>
                <p><strong>Функционал:</strong> Блокировка устройства при >5 обращениях/30 сек, предупреждение пользователю</p>
                <a href="task7_index.php" class="task-link">Открыть задание 7</a>
            </li>
            
            <li class="task-item">
                <h3>Задание 8 [1/8] <span class="status status-completed">✓ Выполнено</span></h3>
                <p><strong>Описание:</strong> Защита от SQL-инъекций</p>
                <p><strong>Функционал:</strong> Prepared statements, валидация входных данных, защита от XSS</p>
                <a href="task8_index.php" class="task-link">Открыть задание 8</a>
            </li>
        </ul>
        
        <div style="margin-top: 30px; padding: 15px; background-color: #e8f5e9; border-radius: 5px;">
            <h3 style="margin-top: 0;">Дополнительные страницы:</h3>
            <ul>
                <li><a href="history.php?device_id=1">История управления устройством (пример)</a></li>
                <li><a href="device_status.php?ID=1&Term=25&Rele=1">API статуса устройства (для устройства)</a></li>
            </ul>
        </div>
        
        <div style="margin-top: 20px; text-align: center; color: #666; font-size: 14px;">
            <p>Все задания выполнены и готовы к проверке</p>
            <p>Подробное описание в файле <strong>README.md</strong></p>
        </div>
    </div>
</body>
</html>

