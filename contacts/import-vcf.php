<?php

/**
 * Скрипт для импорта контактов из VCF-файла (VERSION:3.0) в CRM.
 * 
 * Этот скрипт считывает контакты из VCF-файла и добавляет их в CRM-систему solOS с использованием API.
 * Контакты включают имя, телефон, email и дополнительные поля.
 * 
 * Пример показывает, как использовать API для массового импорта данных из файлов формата vCard.
 * 
 * @see https://www.solos.pro/
 * @see https://www.solos.pro/api/v1/
 * @see https://github.com/artlevitan/solos-examples
 */

// Замените на ваши реальные данные
const CRM_URL = 'YOUR_CRM_URL'; // Адрес вашей CRM системы
const CRM_TOKEN = 'YOUR_CRM_TOKEN'; // Токен авторизации
const CRM_SECRET = 'YOUR_CRM_SECRET'; // Секретный ключ

// const CRM_URL = 'https://demo.solos.pro'; // Адрес вашей CRM системы
// const CRM_TOKEN = '919d7d717a706c61763f5f8622203df94b18e45e6f059fb4a56aad0c677bf1a2'; // Токен авторизации
// const CRM_SECRET = '3d2f6dd538b80770672a543d52ced148c829a8119aef46b28db3a312b26d68855f16fd6a16edd78d361d64afc37acdeee4d424296643833397ac2dd05a43c02d'; // Секретный ключ

const PATH_TO_VCF = 'contacts.vcf'; // Путь к файлу VCF с контактами
const CLIENT_ID = 1; // ID контрагента в CRM, к которому будут привязаны контакты

/**
 * Добавляет контакт в solOS
 *
 * @param int $clientID ID контрагента
 * @param string $name Имя контакта
 * @param string|null $phone_1 Основной телефон
 * @param string|null $email_1 Основной Email
 * @param string|null $position Должность
 * @param string|null $comment Комментарий
 */
function createContact(int $clientID, string $name, string $phone_1 = null, string $email_1 = null, string $position = null, string $comment = null): void
{
    $request_body = [
        "module" => "contacts",
        "payload" => [
            "client_id" => $clientID,
            "name" => $name,
            "phone_1" => $phone_1,
            "email_1" => $email_1,
            "position" => $position,
            "comment" => $comment
        ]
    ];

    $fields = json_encode($request_body);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, CRM_URL . '/api/post/');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type:application/json',
        'X-Crypto-Token:' . CRM_TOKEN,
        'X-Crypto-Secret:' . CRM_SECRET,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Получаем ответ и закрываем cURL
    $response = curl_exec($ch);
    curl_close($ch);

    // Отладка
    // print_r($response); 
}

/**
 * Простой парсер VCF-файла версии 3.0
 *
 * @param string $filePath Путь к VCF-файлу
 * @return array Массив контактов (каждый контакт — это ассоциативный массив)
 */
function parseVcfFile(string $filePath): array
{
    $contacts = [];
    $currentContact = [];

    // Открываем файл VCF
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Если начинается новая карточка контакта
        if (stripos($line, 'BEGIN:VCARD') !== false) {
            $currentContact = []; // Начинаем новый контакт
        }

        // Если карточка контакта закончилась
        if (stripos($line, 'END:VCARD') !== false) {
            // Добавляем текущий контакт в массив всех контактов, если есть необходимые данные
            if (!empty($currentContact)) {
                $contacts[] = $currentContact;
            }
        }

        // Парсим имя (FN)
        if (stripos($line, 'FN:') === 0) {
            $currentContact['name'] = mb_substr($line, 3);
        }

        // Парсим телефон (TEL) — берём только первый телефон
        if ((stripos($line, 'TEL:') === 0 || stripos($line, 'TEL;') === 0) && !isset($currentContact['phone'])) {
            // Извлекаем номер телефона после двоеточия
            preg_match('/TEL.*:(.*)/', $line, $matches);
            if (isset($matches[1])) {
                $phone = trim($matches[1]);
                // Сохраняем первый найденный телефон в текущий контакт
                $currentContact['phone'] = $phone;
            }
        }

        // Парсим email (EMAIL) — берём только первый email
        if ((stripos($line, 'EMAIL:') === 0 || stripos($line, 'EMAIL;') === 0) && !isset($currentContact['email'])) {
            // Извлекаем email после двоеточия
            preg_match('/EMAIL.*:(.*)/', $line, $matches);
            if (isset($matches[1])) {
                $email = trim($matches[1]);
                // Сохраняем первый найденный email в текущий контакт
                $currentContact['email'] = $email;
            }
        }
    }

    return $contacts;
}

// Парсим VCF-файл
$contacts = parseVcfFile(PATH_TO_VCF);

// Перебираем контакты из файла VCF и добавляем их в CRM
foreach ($contacts as $contact) {
    $name = $contact['name'] ?? 'Неизвестный'; // Имя контакта
    $phone = $contact['phone'] ?? '+7'; // Основной телефон
    $email = $contact['email'] ?? ''; // Email
    $position = '-'; // Должность
    $comment = ''; // Комментарий

    // Добавляем контакт в solOS
    createContact(CLIENT_ID, $name, $phone, $email, $position, $comment);
}
