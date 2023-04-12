<?php

try {
    $connection = new PDO('mysql:host=localhost;dbname=cloud_storage;charset=utf8', 'root', '');
} catch (\PDOException $e) {
    echo $e->getMessage();
}

require_once './autoload.php';

/* $routes = include 'routes.php';

$url = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];


if (isset($routes[$url][$method])) {
    $handler = $routes[$url][$method];
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
} */


$routes = include 'routes.php';
$url = parse_url($_SERVER['REQUEST_URI']);
$urlParts = explode("/", $url['path']);
$urlParams = [];
foreach ($urlParts as $part) {
    if (is_numeric($part) || str_contains($part, '@')) {
        $urlParams[] = $part;
    } else {
        $urlPath[] = $part;
    }
}

$url = implode('/', $urlPath);

foreach ($routes as $route => $params) {
    if ($url == $route) {
        foreach ($params as $request_method => $value) {
            $values = explode("::", $value);
            $class = $values[0];
            $method = $values[1];
            if ($_SERVER['REQUEST_METHOD'] == $request_method) {
                $object = new $class;
                $object->$method($urlParams);
                exit;
            }
        }
    }
}
header("HTTP/1.0 404 Not Found");
echo 'Нет такой страницы';
