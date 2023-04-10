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
    '/user/' => [
        'GET' => 'UserClass::showUsers',
        'POST' => 'UserClass::addUser',
        'PUT' => 'UserClass::updateUser',
        'DELETE' => 'UserClass::deleteUser',
    ],
    '/users/{id}' => [
        'GET' => 'UserClass::getUser',
    ]
];

function findRoute($url)
{
    $findUrl = '';
    $paramVar = null;
    foreach ($GLOBALS['urlList'] as $key => $v) :
        // Чтобы найти точное совпадение адреса, надо избавиться от параметров (т.е то что в фигурных скобках)
        if(strpos($url, $key) === false) continue; 
        $findUrl = $key;
        // Вытащить название параметра из фигурных скобок - должен в name получить 'id'
        // Получить значение параметра - должен в val получить 10
        // paramVar будет массив из 2х значений [name, val]
    endforeach;
    if(!$findUrl) return false;
    return ['url' => $findUrl, 'paramVar' => $paramVar];
}


$route = findRoute($_SERVER['REQUEST_URI'])['url'];
$url = $route['url'];
$param = $route['paramVar'];
$method = $_SERVER['REQUEST_METHOD'];


/* $id = end(explode("/", $_SERVER['REQUEST_URI']));

if (empty($id)) {
    $id = null;
} */

if (isset($urlList[$url][$method])) {
    $handler = $urlList[$url][$method];
    $parts = explode('::', $handler);
    $className = $parts[0];
    $methodName = $parts[1];

    if (class_exists($className) && method_exists($className, $methodName)) {
        call_user_func(array($className, $methodName), $param);
    } else {
        http_response_code(404);
        echo 'Метод класса не существует';
    }
} else {
    http_response_code(404);
    echo '404 Not Found';
}
