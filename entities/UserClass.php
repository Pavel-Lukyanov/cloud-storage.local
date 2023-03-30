<?php

require_once '../pdoconfig.php';

// Класс UserClass должен быть определен в файле './entities/UserClass.php'
class UserClass
{
    public function showUsers()
    {
        $statement = $connection->prepare('SELECT * FROM users');
        

    }

    public function addUser()
    {
        return 'Adding user';
    }
}
