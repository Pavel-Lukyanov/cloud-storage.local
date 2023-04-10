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
        $connection = null;
        echo json_encode($data);
    }

    static public function addUser()
    {
        if (!empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['role'])) {
            $email = htmlspecialchars($_POST['email']);
            $password = htmlspecialchars($_POST['password']);
            $role = htmlspecialchars($_POST['role']);
            $password = password_hash($password, PASSWORD_DEFAULT);
            global $connection;
            $statement = $connection->prepare("INSERT INTO users (id, email, password, role) values(null, :email, :password, :role)");
            $statement->execute(['email' => $email, 'password' => $password, 'role' => $role]);
        } else {
            echo "error";
        }
    }

    static public function getUser($param)
    {
        echo $param['val'];
       /*  $url = $_SERVER['REQUEST_URI'];
        $parts = explode('/', $url);
        $user_id = $parts[sizeof($parts) - 1]; // Извлекаем id пользователя из URL
        echo $user_id; */
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

    static public function deleteUser($id)
    {
        global $connection;
        $statement = $connection->prepare('DELETE FROM users where id = :id');
        $statement->execute(['id' => $id]);
    }
}
