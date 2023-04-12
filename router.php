<?php

class Router
{
    public function __construct()
    {
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
    }
}
