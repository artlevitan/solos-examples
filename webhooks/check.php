<?php

/**
 * @see https://github.com/artlevitan/solos-examples
 * 
 * Пример скрипта для обработки вебхуков
 * 
 * Этот простой скрипт является примером обработчика вебхуков, который выводит все полученные параметры POST.
 * Когда этот вебхук вызывается запросом от стороннего сервиса, он захватывает и отображает
 * данные, отправленные в теле POST-запроса, в структурированном виде.
 * 
 * @see https://www.solos.pro/
 * @see https://www.solos.pro/api/v1/
 * @see https://www.solos.pro/help/settings/webhooks/
 */

// Проходимся по данным POST и выводим каждую пару ключ-значение
foreach ($_POST as $key => $val) {
    echo "<div>[$key] : $val</div>";
}
