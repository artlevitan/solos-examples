<?php

/**
 * @see https://github.com/artlevitan/solos-examples
 * 
 * Пример скрипта для выгрузки событий из CRM и генерации файла iCalendar (ICS).
 * 
 * Этот пример подключается к API CRM, извлекает данные о событиях и днях рождения,
 * а затем формирует файл iCalendar (ICS), который можно скачать и импортировать в календарные приложения.
 * 
 * Используется для автоматического экспорта событий в формат, совместимый с iCalendar.
 *
 * @see https://www.solos.pro/
 * @see https://www.solos.pro/api/v1/
 */

// Замените на ваши реальные данные
const CRM_URL = 'YOUR_CRM_URL'; // Адрес вашей CRM системы
const CRM_TOKEN = 'YOUR_CRM_TOKEN'; // Токен авторизации
const CRM_SECRET = 'YOUR_CRM_SECRET'; // Секретный ключ

// const CRM_URL = 'https://demo.solos.pro'; // Адрес вашей CRM системы
// const CRM_TOKEN = '919d7d717a706c61763f5f8622203df94b18e45e6f059fb4a56aad0c677bf1a2'; // Токен авторизации
// const CRM_SECRET = '3d2f6dd538b80770672a543d52ced148c829a8119aef46b28db3a312b26d68855f16fd6a16edd78d361d64afc37acdeee4d424296643833397ac2dd05a43c02d'; // Секретный ключ

/**
 * Получает данные из solOS
 *
 * @param string $module Название модуля CRM для извлечения данных.
 * @param int $count Количество записей для извлечения.
 * @param int $offset Смещение для пагинации.
 * @param string $order Порядок сортировки.
 * @return array Массив данных, полученных от API.
 */
function getEvents(string $module, int $count, int $offset, string $order = ''): array
{
    $request_body = [
        "module" => $module,
        "count" => $count,
        "offset" => $offset,
    ];

    if ($order) {
        $request_body['order'] = $order;
    }

    // Настройка cURL для отправки запроса
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, CRM_URL . '/api/get/');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type:application/json',
        'X-Crypto-Token:' . CRM_TOKEN,
        'X-Crypto-Secret:' . CRM_SECRET,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_body));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Получаем ответ и закрываем cURL
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Отладка
    // print_r($response); 

    // Проверяем успешный ответ
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return $result['payload'] ?? [];
    }

    return [];
}

// Получаем события из Календаря
$calendarData = getEvents('calendar', 500, 0);

// Получаем данные о днях рождения из Контактов
$birthdaysData = getEvents('contacts/birthdays', 500, 0, 'asc');

// Инициализация строки iCalendar
$ical = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//SOLOS//EN
CALSCALE:GREGORIAN' . "\n";

// Формирование событий календаря для iCalendar
foreach ($calendarData as $event) {
    $description = str_replace(["\r", "\n", "\t"], '\n', $event['description']);
    $allDay = $event['all_day'] == '1';

    $ical .= 'BEGIN:VEVENT
TZID:' . date_default_timezone_get() . "\n";

    if ($allDay) {
        $ical .= 'DTEND;VALUE=DATE:' . date('Ymd', strtotime($event['end'])) . "\n";
        $ical .= 'DTSTART;VALUE=DATE:' . date('Ymd', strtotime($event['start'])) . "\n";
    } else {
        $ical .= 'DTEND:' . date('Ymd\THis', strtotime($event['end'])) . "\n";
        $ical .= 'DTSTART:' . date('Ymd\THis', strtotime($event['start'])) . "\n";
    }

    $ical .= 'LAST-MODIFIED:' . date('Ymd\THis', time()) . "\n";
    $ical .= 'DTSTAMP:' . date('Ymd\THis', time()) . "\n";
    $ical .= 'SUMMARY:' . addslashes($event['category']) . ' – ' . addslashes($event['name']) . "\n";
    $ical .= 'DESCRIPTION:' . $description . "\n";
    $ical .= 'UID:' . sha1(CRM_URL . '_' . $event['id']) . "\n";
    $ical .= 'CREATED:' . date('Ymd\THis', time()) . "\n";
    $ical .= 'END:VEVENT' . "\n";
}

// Формирование данных о днях рождения для iCalendar
foreach ($birthdaysData as $bdays) {
    $ical .= 'BEGIN:VEVENT
TZID:' . date_default_timezone_get() . "\n";
    $ical .= 'DTEND;VALUE=DATE:' . date('Y') . date('md', strtotime($bdays['bday'])) . "\n";
    $ical .= 'DTSTART;VALUE=DATE:' . date('Y') . date('md', strtotime($bdays['bday'])) . "\n";
    $ical .= 'LAST-MODIFIED:' . date('Ymd\THis', time()) . "\n";
    $ical .= 'DTSTAMP:' . date('Ymd\THis', time()) . "\n";
    $ical .= 'SUMMARY:Д. р. – ' . addslashes($bdays['name']) . "\n";
    $ical .= 'DESCRIPTION:' . addslashes($bdays['client']) . "\n";
    $ical .= 'UID:' . sha1(CRM_URL . '_bday_' . $bdays['id']) . "\n";
    $ical .= 'END:VEVENT' . "\n";
}

// Завершение строки iCalendar
$ical .= 'END:VCALENDAR';

// Отправляем файл iCalendar на скачивание
header('Content-Description: File Transfer');
header('Content-Type: text/calendar; charset=UTF-8');
header('Content-Disposition: attachment; filename="events_export.ics"');
header('Expires: 0');
header('Pragma: public');
header('Cache-Control: no-store, no-cache');
echo $ical;
