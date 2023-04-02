<?php

class UserClass
{
    static public function showUsers()
    {
        global $connection;
        $statement = $connection->query("SELECT email, id FROM users");
        $statement->execute();
        $data = [];
        $data = $statement->fetchAll();
        $connection = null; // закрываем подключение к базе данных
        echo json_encode($data);
    }

    static public function addUser()
    {
        if (!empty($_POST['email']) && !empty($_POST['password'])) {
            $email = htmlspecialchars($_POST['email']);
            $password = htmlspecialchars($_POST['password']);
            $password = password_hash($password, PASSWORD_DEFAULT);
            global $connection;
            $statement = $connection->prepare("INSERT INTO users(id, email, password) values(null, :email, :password)");
            $statement->execute(['email' => $email, 'password' => $password]);
        } else {
            echo "error";
        }
    }

    static public function getUser($id)
    {
        
    }

    static public function updateUser()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $_POST = array();

            var_dump($_POST);



            /* $id = $_POST['id'];
            $email = htmlspecialchars($_POST['email']);
            $password = htmlspecialchars($_POST['password']);
            $password = password_hash($password, PASSWORD_DEFAULT);
            global $connection;
            $statementUpdate = $connection->prepare("UPDATE users SET email = :email password = :password WHERE id = :id");
            $statementUpdate->execute(['id' => $id, 'email' => $email, 'password' => $password]); */
        } else {
            echo "error";
        }
    }
}
