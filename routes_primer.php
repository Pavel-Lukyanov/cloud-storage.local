<?php

return array(
    '/user' => [
        'POST' => 'user::addUser',
        'GET' => 'UserClass::getUser',
        'PUT' => 'user::updateUser',
        'DELETE' => 'user::deleteUser'
    ],
    '/user/login' => ['GET' => 'user::login'],
    '/user/logout' => ['GET' => 'user::logout'],
    '/user/reset-password' => ['GET' => 'user::resetPassword'],
    '/users' => ['GET' => 'user::getUser'],
    '/admin/user' => [
        'GET' => 'admin::userList',
        'PUT' => 'admin::updateUser',
        'DELETE' => 'admin::deleteUser'
    ],
    '/file' => [
        'GET' => 'file::getFIle',
        'POST' => 'file::addFile',
        'PUT' => 'file::editFile',
        'DELETE' => 'file::deleteFile'
    ],
    '/directory' => [
        'POST' => 'file::addDir',
        'PUT' => 'file::renameDir',
        'GET' => 'file::getDir',
        'DELETE' => 'file::deleteDIr'
    ],
    '/files/share' => [
        'GET' => 'file::getFileAccess',
        'PUT' => 'file::addFileAccess',
        'DELETE' => 'file::deleteFileAccess'
    ],
    '/user/search' => ['GET' => 'user::checkUserEmail']
);
