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
        'Content-Type: application/json',
        'X-Crypto-Token: ' . CRM_TOKEN,
        'X-Crypto-Secret: ' . CRM_SECRET,
        'User-Agent: solOS/1.0',
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
$ical = "BEGIN:VCALENDAR\r\n";
$ical .= "VERSION:2.0\r\n";
$ical .= "PRODID:-//SOLOS//RU\r\n";
$ical .= "CALSCALE:GREGORIAN\r\n";

$timezone = addslashes(date_default_timezone_get());

foreach ($calendarData as $event) {
    $eventTitle = $event['category'] . ' – ' . $event['name'];
    $eventTitle = str_replace(["\\", ",", ";", "\r", "\n"], ["\\\\", "\\,", "\\;", '', '\\n'], $eventTitle);
    $description = !empty($event['description']) ? str_replace(["\\", ",", ";", "\r", "\n"], ["\\\\", "\\,", "\\;", '', '\\n'], $event['description']) : '';
    $uID = sha1(CRM_URL . '_calendar_' . $event['id']);
    $allDay = $event['all_day'] == '1';

    $ical .= "BEGIN:VEVENT\r\n";
    $ical .= "UID:" . $uID . "\r\n";
    $ical .= "CREATED:" . date('Ymd\THis', time()) . "\r\n";
    $ical .= "LAST-MODIFIED:" . date('Ymd\THis', time()) . "\r\n";
    $ical .= "DTSTAMP:" . date('Ymd\THis', time()) . "\r\n";

    if ($allDay) {
        $ical .= "DTSTART;VALUE=DATE:" . date('Ymd', strtotime($event['start'])) . "\r\n";
        $ical .= "DTEND;VALUE=DATE:" . date('Ymd', strtotime($event['end'])) . "\r\n";
    } else {
        $ical .= "DTSTART;TZID=" . $timezone . ":" . date('Ymd\THis', strtotime($event['start'])) . "\r\n";
        $ical .= "DTEND;TZID=" . $timezone . ":" . date('Ymd\THis', strtotime($event['end'])) . "\r\n";
    }

    $ical .= "SUMMARY:" . $eventTitle . "\r\n";
    $ical .= "DESCRIPTION:" . $description . "\r\n";

    if (!$allDay) {
        $ical .= "BEGIN:VALARM\r\n";
        $ical .= "TRIGGER:-PT15M\r\n";
        $ical .= "ACTION:DISPLAY\r\n";
        $ical .= "DESCRIPTION:" . $eventTitle . "\r\n";
        $ical .= "END:VALARM\r\n";
    }

    $ical .= "END:VEVENT\r\n";
}

foreach ($birthdaysData as $bdays) {
    $eventTitle = 'Д. р. – ' . addslashes($bdays['name']);
    $uID = sha1(CRM_URL . '_bday_' . $bdays['id']);

    $ical .= "BEGIN:VEVENT\r\n";
    $ical .= "UID:" . $uID . "\r\n";
    $ical .= "CREATED:" . date('Ymd\THis', time()) . "\r\n";
    $ical .= "LAST-MODIFIED:" . date('Ymd\THis', time()) . "\r\n";
    $ical .= "DTSTAMP:" . date('Ymd\THis', time()) . "\r\n";
    $ical .= "DTSTART;VALUE=DATE:" . date('Y') . date('md', strtotime($bdays['bday'])) . "\r\n";
    $ical .= "DTEND;VALUE=DATE:" . date('Y') . date('md', strtotime($bdays['bday'])) . "\r\n";
    $ical .= "SUMMARY:" . $eventTitle . "\r\n";
    $ical .= "DESCRIPTION:" . addslashes($bdays['client']) . "\r\n";
    $ical .= "END:VEVENT\r\n";
}

$ical .= "END:VCALENDAR\r\n";

// Отправляем файл iCalendar на скачивание
header('Content-Description: File Transfer');
header('Content-Type: text/calendar; charset=UTF-8');
header('Content-Disposition: attachment; filename="events_export.ics"');
header('Expires: 0');
header('Pragma: public');
header('Cache-Control: no-store, no-cache');
echo $ical;
