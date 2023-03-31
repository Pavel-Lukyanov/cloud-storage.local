<?php

try {
    $connection = new PDO('mysql:host=localhost;dbname=cloud_storage;charset=utf8', 'root', '');
} catch (\PDOException $e) {
    echo $e->getMessage();
}

require_once './autoload.php';

$urlList = [
    '/' => [
        'GET' => 'MainClass::show',
    ],
    '/users/' => [
        'GET' => 'UserClass::showUsers',
    ],
];


$url = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if (isset($urlList[$url][$method])) {
    $handler = $urlList[$url][$method];
    $parts = explode('::', $handler);
    $className = $parts[0];
    $methodName = $parts[1];


    if (class_exists($className) && method_exists($className, $methodName)) {
        call_user_func(array($className, $methodName));
    } else {
        http_response_code(404);
        echo 'Метод класса не существует';
    }
} else {
    http_response_code(404);
    echo '404 Not Found';
}
