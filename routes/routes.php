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
    '/reset-password/' => [
        'POST' => 'UserClass::resetPassword',
    ],
    '/new-password/' => [
        'POST' => 'UserClass::newPassword',
    ],
    '/users/' => [
        'GET' => 'UserClass::getUser',
    ],
    '/admin/user/' => [
        'GET' => 'AdminClass::showUsers',
        'DELETE' => 'AdminClass::deleteUser',
        'PUT' => 'AdminClass::updateUser',
    ],
    '/file/' => [
        'POST' => 'FileClass::addFile',
        'GET' => 'FileClass::showFile',
        'DELETE' => 'FileClass::deleteFile',
    ],
);
