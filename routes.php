<?php
return array(
    '/user/' => [
        'GET' => 'UserClass::showUsers',
        'POST' => 'UserClass::addUser',
        'PUT' => 'UserClass::updateUser',
        'DELETE' => 'UserClass::deleteUser',
    ],
    '/login/' => [
        'POST' => 'UserClass::loginUser',
    ],
    '/logout/' => [
        'GET' => 'UserClass::logoutUser',
    ],
    '/users/' => [
        'GET' => 'UserClass::getUser',
    ]
);
