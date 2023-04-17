<?php

class UserClass
{
    //Показать всех пользователей
    static public function showUsers()
    {
        try {
            global $connection;
            $statement = $connection->query("SELECT id, email FROM users");
            $statement->execute();
            $data = [];
            $data = $statement->fetchAll();
            $connection = null;
            if (empty($data)) {
                throw new Exception("Users not found!");
            } else {
                echo json_encode($data);
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    static public function addUser()
    {
        try {
            if (isset($_POST['email'], $_POST['password'], $_POST['role'])) {
                $email = htmlspecialchars($_POST['email']);
                $password = htmlspecialchars($_POST['password']);
                $role = htmlspecialchars($_POST['role']);
                $password = password_hash($password, PASSWORD_DEFAULT);
                global $connection;
                $statement = $connection->prepare("INSERT INTO users (id, email, password, role) values(null, :email, :password, :role)");
                $statement->execute(['email' => $email, 'password' => $password, 'role' => $role]);
                // Возвращаем успешный ответ с данными пользователя
                $user_id = $connection->lastInsertId();
                $data = [
                    'id' => $user_id,
                    'email' => $email,
                    'role' => $role
                ];
                header('HTTP/1.1 201 Created');
                header('Content-Type: application/json');
                echo json_encode($data);
            } else {
                throw new Exception('Не все данные были переданы!');
            }
        } catch (PDOException $e) {
            echo "Ошибка базы данных: " . $e->getMessage();
        } catch (Exception $e) {
            // Остальные ошибки
            echo "Ошибка: " . $e->getMessage();
        }
    }

    //Пользователь по id
    static public function getUser()
    {
        try {
            $url = $_SERVER['REQUEST_URI'];
            $parts = explode('/', $url);
            $user_id = $parts[sizeof($parts) - 1]; // Извлекаем id пользователя из URL
            global $connection;
            $statement = $connection->query("SELECT id, email FROM users WHERE id = {$user_id}");
            $statement->execute();
            $data = [];
            $data = $statement->fetchAll();
            $connection = null;
            if (empty($data)) {
                throw new Exception("User with id {$user_id} not found!");
            } else {
                echo json_encode($data);
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    static public function updateUser()
    {
        try {
            $url = $_SERVER['REQUEST_URI'];
            $parts = explode('/', $url);
            $user_id = $parts[sizeof($parts) - 1];
            $data = json_decode(file_get_contents("php://input"), true);

            if (!empty($data['email']) && !empty($data['role'])) {
                $email = htmlspecialchars($data['email']);
                $role = htmlspecialchars($data['role']);
                global $connection;
                $statement = $connection->prepare('UPDATE users SET email = :email, role = :role WHERE id = :id');
                $statement->execute(['email' => $email, 'role' => $role, 'id' => $user_id]);

                // Возвращаем успешный ответ с обновленными данными пользователя
                $statement = $connection->prepare("SELECT id, email, role FROM users WHERE id = :id");
                $statement->execute(['id' => $user_id]);
                $data = $statement->fetch(PDO::FETCH_ASSOC);
                header('HTTP/1.1 200 OK');
                header('Content-Type: application/json');
                echo json_encode($data);
            } else {
                // Исключение, если недостаточно данных
                throw new Exception('Не хватает данных');
            }
        } catch (PDOException $e) {
            // Ошибка базы данных
            echo "Ошибка базы данных: " . $e->getMessage();
        } catch (Exception $e) {
            // Остальные ошибки, включая исключение "Не хватает данных"
            echo "Ошибка: " . $e->getMessage();
        }
    }

    static public function deleteUser()
    {
        try {
            $url = $_SERVER['REQUEST_URI'];
            $parts = explode('/', $url);
            $user_id = $parts[sizeof($parts) - 1];

            global $connection;
            $statement = $connection->prepare('DELETE FROM users where id = :id');
            $statement->execute(['id' => $user_id]);

            // Устанавливаем HTTP-статус "204 No Content"
            http_response_code(204);
        } catch (PDOException $e) {
            // Ошибка базы данных
            echo "Ошибка базы данных: " . $e->getMessage();
        } catch (Exception $e) {
            // Остальные ошибки
            echo "Ошибка: " . $e->getMessage();
        }
    }

    static public function loginUser()
    {
        $username = htmlspecialchars($_POST['login']);
        $password = htmlspecialchars($_POST['password']);
        $password = password_hash($password, PASSWORD_DEFAULT);

        global $connection;
        $statement = $connection->query('SELECT id, email FROM users WHERE email :email AND password :password');
        $statement->execute();
        $data = [];
        $data = $statement->fetchAll();
        echo '<pre>'; var_dump($data); echo '</pre>';
        $connection = null;
    }
}