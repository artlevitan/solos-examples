<?php

/**
 * Пример виджета-фрейма для solOS.
 * 
 * Этот скрипт представляет собой базовый пример виджета-фрейма,
 * который можно встроить в систему solOS. Он включает функции смены темы (светлая/тёмная),
 * установки языка интерфейса, а также автоматическую перезагрузку страницы каждые 60 секунд.
 * 
 * @see https://www.solos.pro/
 * @see https://www.solos.pro/api/v1/
 * @see https://github.com/artlevitan/solos-examples
 */

// Получение адреса CRM-системы через параметр URL, если не задан — пустая строка
$solosServer = $_GET['solos_server'] ?? '';

// Проверка, включена ли тёмная тема
$isDarkTheme = isset($_GET['solos_theme']) && $_GET['solos_theme'] === 'dark';

// Установка языка интерфейса, по умолчанию — русский
$solosLang = $_GET['solos_lang'] ?? 'ru';
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($solosLang); ?>">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Тег автоматической перезагрузки страницы каждые 60 секунд -->
    <meta http-equiv="refresh" content="<?= 60 - intval(date('s')); ?>">

    <!-- Установка адаптивного режима отображения -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Виджет-фрейм для solOS</title>

    <!-- Подключение базового стиля из CRM -->
    <link rel="stylesheet" href="<?= htmlspecialchars($solosServer); ?>/css/base.css">

    <!-- Подключение стиля для тёмной темы, если активирована -->
    <?php if ($isDarkTheme): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($solosServer); ?>/css/dark.css">
    <?php endif; ?>
</head>

<body class="m-0 p-0">
    <div>Привет, я виджет-фрейм для solOS!</div>
</body>

</html>