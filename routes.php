<?php
return array(
    '/user' => [
        'GET' => 'UserClass::getUser',
        'POST' => 'UserClass::addUser',
        'PUT' => 'UserClass::updateUser',
        'DELETE' => 'UserClass::deleteUser',
    ],
    '/users' => [
        'GET' => 'UserClass::showUsers',
    ]
);
