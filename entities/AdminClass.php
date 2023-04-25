<?php

class AdminClass
{
    static public function showUsers()
    {
        header('Content-Type: application/json');
        $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : (isset($_SERVER['HTTP_X_AUTHORIZATION']) ? $_SERVER['HTTP_X_AUTHORIZATION'] : '');
        if (empty($authHeader)) {
            http_response_code(400);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        // Получаем access token из заголовка авторизации
        $bearerToken = explode(" ", $authHeader);
        if (count($bearerToken) != 2 || $bearerToken[0] !== 'Bearer' || empty($bearerToken[1])) {
            http_response_code(400);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        $accessToken = $bearerToken[1];

        //Получаем id юзера по токену
        global $connection;
        $statement = $connection->prepare('SELECT user_id, expiration_time FROM user_tokens WHERE token = :token');
        $statement->execute(['token' => $accessToken]);
        $data = $statement->fetch(PDO::FETCH_ASSOC); // извлечение данных из запроса
        if (!empty($data)) {
            $created_at_datetime = DateTime::createFromFormat('Y-m-d H:i:s', $data['expiration_time']);
            $current_time = new DateTime();
            if (($current_time->getTimestamp() > $created_at_datetime->getTimestamp())) {
                // Токен устарел
                $statement = $connection->prepare('DELETE FROM user_tokens WHERE token = :token');
                $statement->execute(['token' => $accessToken]);
                http_response_code(400);
                echo json_encode(['error' => 'Token has expired']);
                return;
            } else {
                // Срок жизни токена не истек
                $user_id = $data['user_id'];
                //Достаем роль пользователя
                $statement = $connection->prepare('SELECT id, role, email FROM users WHERE id = :id');
                $statement->execute(['id' => $user_id]);
                $data = $statement->fetch(PDO::FETCH_ASSOC);
                //Если роль админ
                if ($data['role'] === 'admin') {
                    $url = $_SERVER['REQUEST_URI'];
                    $parts = explode('/', $url);
                    $user_id = $parts[sizeof($parts) - 1]; // Извлекаем id пользователя из URL

                    //Проверка на присутствие параметра в URL
                    if (empty($user_id)) {
                        self::showAllUsers();
                    } else {
                        self::getUserID($user_id);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Access denied']);
                    return;
                }
                $connection = null;
            }
        } else {
            http_response_code(400);
            echo 'Error: Access denied';
            return;
        }
    }

    private static function showAllUsers()
    {
        try {
            global $connection;
            $statement = $connection->query("SELECT id, email, role FROM users");
            $statement->execute();
            $data = [];
            $data = $statement->fetchAll();
            $connection = null;
            if (empty($data)) {
                http_response_code(404);
                echo json_encode(['error' => 'Users not found']);
                return;
            } else {
                echo json_encode(['users' => $data]);
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    private static function getUserID($id)
    {
        try {
            if (!empty(htmlspecialchars(trim($id)))) {
                global $connection;
                $statement = $connection->prepare("SELECT id, email, role FROM users WHERE id = :id");
                $statement->execute(['id' => $id]);
                $user = [];
                $user = $statement->fetch(PDO::FETCH_ASSOC);
                $connection = null;
                if (empty($user) || !is_numeric($id)) {
                    throw new Exception("User with id {$id} not found!");
                } else {
                    echo json_encode(['user' => $user]);
                }
            } else {
                echo "Error: user not found";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    static public function deleteUser()
    {
        echo 'Удалить юзера';
    }

    static public function updateUser()
    {
        echo 'Обновить инфу о юзере';
    }
}
