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
        header('Content-Type: application/json');
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
        header('Content-Type: application/json');
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
        header('Content-Type: application/json');
        $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : (isset($_SERVER['HTTP_X_AUTHORIZATION']) ? $_SERVER['HTTP_X_AUTHORIZATION'] : '');
        if (empty($authHeader)) {
            http_response_code(401);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        // Получаем access token из заголовка авторизации
        $bearerToken = explode(" ", $authHeader);
        if (count($bearerToken) != 2 || $bearerToken[0] !== 'Bearer' || empty($bearerToken[1])) {
            http_response_code(401);
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
                http_response_code(401);
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
                    if (!empty($user_id)) {
                        //Удаляем пользователя
                        try {
                            global $connection;
                            $statement = $connection->prepare('SELECT COUNT(*) FROM users WHERE id = :id');
                            $statement->execute(['id' => $user_id]);

                            $user_exists = $statement->fetchColumn();

                            if ($user_exists) {
                                $statement = $connection->prepare('DELETE FROM users WHERE id = :id');
                                $statement->execute(['id' => $user_id]);
                                http_response_code(204);
                            } else {
                                http_response_code(404);
                                echo "User not found";
                            }
                        } catch (PDOException $e) {
                            echo "Error database: " . $e->getMessage();
                            http_response_code(500);
                        } catch (Exception $e) {
                            echo "Error: " . $e->getMessage();
                            http_response_code(500);
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Bad Request']);
                        return;
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Access denied']);
                    return;
                }
                $connection = null;
            }
        } else {
            http_response_code(401);
            echo 'Error: Access denied';
            return;
        }
    }

    static public function updateUser()
    {
        header('Content-Type: application/json');
        $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : (isset($_SERVER['HTTP_X_AUTHORIZATION']) ? $_SERVER['HTTP_X_AUTHORIZATION'] : '');
        if (empty($authHeader)) {
            http_response_code(401);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
        // Получаем access token из заголовка авторизации
        $bearerToken = explode(" ", $authHeader);
        if (count($bearerToken) != 2 || $bearerToken[0] !== 'Bearer' || empty($bearerToken[1])) {
            http_response_code(401);
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
                http_response_code(401);
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
                    $dataCome = json_decode(file_get_contents('php://input'), true);
                    $email = htmlspecialchars(trim($dataCome['email']));
                    $role = htmlspecialchars(trim($dataCome['role']));
                    $id = htmlspecialchars(trim($dataCome['id']));

                    if (!empty($email) && !empty($role) && !empty($id)) {
                        global $connection;
                        $statement = $connection->prepare('UPDATE users SET email = :email, role = :role WHERE id = :id');
                        $statement->execute(['email' => $email, 'role' => $role, 'id' => $id]);

                        // Возвращаем успешный ответ с обновленными данными пользователя
                        $statement = $connection->prepare("SELECT id, email, role FROM users WHERE id = :id");
                        $statement->execute(['id' => $id]);
                        $data = $statement->fetch(PDO::FETCH_ASSOC);
                        header('HTTP/1.1 200 OK');
                        header('Content-Type: application/json');
                        echo json_encode($data);
                    } else {
                        // Исключение, если недостаточно данных
                        throw new Exception('Не хватает данных');
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Access denied']);
                    return;
                }
                $connection = null;
            }
        } else {
            http_response_code(401);
            echo 'Error: Access denied';
            return;
        }
    }
}
