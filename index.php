<?php

// Подключаем autoload.
require_once __DIR__ . '/autoload.php';

// Объявляем список URL и соответствующих запросов.
$urlList = [
    '/users/' => [
        'GET' => 'showUsers',
    ],
    '/users/' => [
        'POST' => 'addUser',
    ],
];

// Определяем HTTP-метод и запрашиваемый URL.
$httpMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Удаляем GET-параметры из URL.
if (($pos = strpos($requestUri, '?')) !== false) {
    $requestUri = substr($requestUri, 0, $pos);
}

// Ищем соответствующий URL в списке.
if (isset($urlList[$requestUri][$httpMethod])) {

    $handler = $urlList[$requestUri][$httpMethod];
    $result = call_user_func([new UserClass(), $handler]);
    echo $result;
} else {
    // URL не найден в списке.
    http_response_code(404);
    echo '404 Not Found';
}
