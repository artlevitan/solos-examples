<?php

/**
 * Пример интеграции формы обратной связи Tilda с системой solOS
 * 
 * Этот скрипт принимает данные, отправленные с формы Tilda через Webhook,
 * и добавляет лид в CRM с использованием API.
 *
 * @see https://www.solos.pro/
 * @see https://www.solos.pro/api/v1/
 * @see https://github.com/artlevitan/solos-examples
 * 
 * @see https://help-ru.tilda.cc/forms/webhook
 */

// Замените на ваши реальные данные
const CRM_URL = 'YOUR_CRM_URL'; // Адрес вашей CRM системы
const CRM_TOKEN = 'YOUR_CRM_TOKEN'; // Токен авторизации
const CRM_SECRET = 'YOUR_CRM_SECRET'; // Секретный ключ

// const CRM_URL = 'https://demo.solos.pro'; // Адрес вашей CRM системы
// const CRM_TOKEN = '919d7d717a706c61763f5f8622203df94b18e45e6f059fb4a56aad0c677bf1a2'; // Токен авторизации
// const CRM_SECRET = '3d2f6dd538b80770672a543d52ced148c829a8119aef46b28db3a312b26d68855f16fd6a16edd78d361d64afc37acdeee4d424296643833397ac2dd05a43c02d'; // Секретный ключ

// ID ответственного пользователя CRM, которому будет назначен лид
const CRM_USER_ID = 1;

// ID источника обращения
// @see https://www.solos.pro/help/settings/303/
const CRM_SOURCE_ID = 4;

/**
 * Возвращает метод HTTP-запроса (GET, POST и т.д.)
 * 
 * @return string Метод запроса (в верхнем регистре)
 */
function getMethod(): string
{
    return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
}

/**
 * Проверяет, является ли запрос методом POST
 * 
 * @return bool True, если запрос POST
 */
function isPost(): bool
{
    return getMethod() === 'POST';
}

/**
 * Очищает строку от HTML-тегов и пробелов
 * 
 * @param string $string Строка для очистки
 * @return string Очищенная строка
 */
function clearString(string $string): string
{
    return trim(strip_tags($string));
}

/**
 * Обрезает строку
 *
 * @param string|null $string Исходная строка
 * @param int $length Длина обрезки
 * @return string
 */
function cutString(?string $string = null, int $length = 100): string
{
    return $string ? mb_strimwidth($string, 0, $length, '..') : '';
}

/**
 * Добавляет лид в solOS
 * 
 * @param string $name Название лида
 * @param string $email Email
 * @param string $phone Телефон
 * @param string $comment Комментарий
 * @param array $extra Служебная информация
 */
function createLead(string $name, string $email, string $phone, string $comment, array $extra): void
{
    $request_body = [
        "module" => "leads",
        "payload" => [
            "user_id" => CRM_USER_ID, // ID ответственного пользователя
            "name" => $name,
            "phone" => $phone,
            "email" => $email,
            "reference_id" => CRM_SOURCE_ID, // ID источника обращения
            "comment" => $comment,
            "extra" => $extra,
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

// Если запрос пришёл методом POST
if (isPost()) {
    // Получаем данные, переданные Tilda через Webhook
    $name = $_POST['Name'] ?? '';
    $name = clearString($name);
    $name = cutString($name, 100);
    $name = urldecode($name);
    //
    $email = $_POST['Email'] ?? '';
    $email = clearString($email);
    $email = cutString($email, 100);
    $email = urldecode($email);
    //
    $phone = $_POST['Phone'] ?? '';
    $phone = clearString($phone);
    $phone = cutString($phone, 50);
    $phone = urldecode($phone);
    //
    $comments = $_POST['Comments'] ?? '';
    $comments = clearString($comments);
    $comments = cutString($comments, 1000);
    $comments = urldecode($comments);

    // Собираем дополнительные данные из формы (передаются через Webhook)
    $extra = [];
    foreach ($_POST as $key => $value) {
        $extra[$key] = clearString($value);
    }

    // Добавляем лид в solOS
    createLead($name, $email, $phone, $comments, $extra);

    // Возвращаем ответ для Tilda
    echo "ok";
}
