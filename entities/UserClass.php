<?php

class UserClass
{
    static public function showUsers()
    {
        global $connection;
        $statement = $connection->query("SELECT * FROM user");
        $statement->execute();
        $data=[];
        $data = $statement->fetchAll();
        $connection = null; // закрываем подключение к базе данных
        echo '<pre>'; var_dump($data); echo '</pre>';
    }

    static public function addUser()
    {
        echo 'add class user';
    }
}
