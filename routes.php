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
    '/users/' => [
        'GET' => 'UserClass::getUser',
    ]
);
