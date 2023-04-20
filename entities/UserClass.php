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

    // Создание нового пользователя
    static public function addUser()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if ((isset($data['email'], $data['password'], $data['role'])) && (!empty(trim($data['email'])) && !empty(trim($data['password'])) && !empty(trim($data['role'])))) {

                $email = htmlspecialchars(trim($data['email']));
                $role = htmlspecialchars(trim($data['role']));
                $password = trim($data['password']);
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
            echo "Error data base: " . $e->getMessage();
        } catch (Exception $e) {
            // Остальные ошибки
            echo "Error: " . $e->getMessage();
        }
    }

    // Получение пользователя по id
    static public function getUser()
    {
        try {
            $url = $_SERVER['REQUEST_URI'];
            $parts = explode('/', $url);
            $user_id = $parts[sizeof($parts) - 1]; // Извлекаем id пользователя из URL
            global $connection;
            $statement = $connection->query("SELECT id, email FROM users WHERE id = :id");
            $statement->execute(['id' => $user_id]);
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

    // Обновление данных пользователя
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
            echo "Error data base: " . $e->getMessage();
        } catch (Exception $e) {
            // Остальные ошибки, включая исключение "Не хватает данных"
            echo "Error: " . $e->getMessage();
        }
    }

    //Удаление пользователя
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
            echo "Error data base: " . $e->getMessage();
        } catch (Exception $e) {
            // Остальные ошибки
            echo "Error: " . $e->getMessage();
        }
    }

    // Аутентификация пользователя
    static public function loginUser()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if ((isset($data['email'], $data['password']))  && (!empty(trim($data['email'])) && !empty(trim($data['password'])))) {
                $email = htmlspecialchars($data['email']);
                $password = trim($data['password']);

                global $connection;
                $statement = $connection->prepare("SELECT id, password FROM users WHERE email = :email");
                $statement->execute(['email' => $email]);
                $user = $statement->fetch(PDO::FETCH_ASSOC);

                if (!password_verify($password, $user['password'])) {
                    http_response_code(401);
                    echo 'Error: Login information not provided';
                    return;
                    $connection = null;
                }

                $bytes = random_bytes(32);
                // Хешируем сгенерированную последовательность байт с помощью SHA-256
                $token = hash('sha256', $bytes);
                $expires_in = time() + (24 * 60 * 60);
                $expiration_time = date('Y-m-d H:i:s', $expires_in);

                $statement = $connection->prepare("INSERT INTO user_tokens (id, user_id, token, expiration_time) values(null, :user_id, :token, :expiration_time)");
                $statement->execute(['user_id' => $user['id'], 'token' => $token, 'expiration_time' => $expiration_time]);
                $connection = null;

                $response = ['access_token' => $token, 'token_type' => 'Bearer', 'expires_in' => 3600];
                echo json_encode($response);
            } else {
                http_response_code(400);
                echo 'Error: Login information not provided';
            }
        } catch (Exception $e) {
            // Остальные ошибки
            echo "Error: " . $e->getMessage();
        }
    }

    // logout пользователя
    static public function logoutUser()
    {
        try {
            $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : (isset($_SERVER['HTTP_X_AUTHORIZATION']) ? $_SERVER['HTTP_X_AUTHORIZATION'] : null);
            if (empty($authHeader)) {
                return http_response_code(400);
            }
            $bearerToken = explode(" ", $authHeader);
            if (count($bearerToken) < 2 || !isset($bearerToken[1])) {
                return http_response_code(400);
            }
            $accessToken = $bearerToken[1];

            global $connection;
            $statement = $connection->prepare('DELETE FROM user_tokens WHERE token = :token');
            $statement->execute(['token' => $accessToken]);

            return "[OK]";
        } catch (PDOException $e) {
            return http_response_code(500);
        }
    }

    //Запрос на изменение пароля (отправляем письмо на почту)
    static public function resetPassword()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $email = htmlspecialchars(trim($data['email']));
        if (empty($email)) {
            http_response_code(400);
            echo 'Error: Login information not provided';
            return;
        }

        try {
            global $connection;
            $statement = $connection->prepare("SELECT email, id FROM users WHERE email = :email");
            $statement->execute(['email' => $email]);
            $user = $statement->fetch(PDO::FETCH_ASSOC); // извлечение данных из запроса
            if (!empty($user)) {
                $bytes = random_bytes(32);
                $token = hash('sha256', $bytes);
                $expires_in = time() + (1 * 60 * 60);
                $expiration_time = date('Y-m-d H:i:s', $expires_in);
                $statement = $connection->prepare("INSERT INTO user_tokens (id, user_id, token, expiration_time) values(null, :user_id, :token, :expiration_time)");
                $statement->execute(['user_id' => $user['id'], 'token' => $token, 'expiration_time' => $expiration_time]);
                $connection = null;

                // Отправляем запрос на другой endpoint API для отправки письма
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "http://cloud-storage.local/sendmail.php");
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('token' => $token, 'email' => $email)));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);
                echo json_encode($response);

                curl_close($ch);
            } else {
                http_response_code(404);
                echo "Error: User with this email was not found";
            }
        } catch (PDOException $e) {
            echo "Connection failed:" . $e->getMessage();
        }
        $connection = null;
    }

    // Ввод нового пароля и прверка токена с почты
    static public function newPassword()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty(htmlspecialchars(trim($data['token']))) || empty(trim($data['password']))) {
            http_response_code(400);
            echo 'Error: Access to change password denied';
            return;
        } else {
            $token = htmlspecialchars(trim($data['token']));
            $password = password_hash(trim($data['password']), PASSWORD_DEFAULT);
            global $connection;
            $statement = $connection->prepare("SELECT user_id, expiration_time FROM user_tokens WHERE token = :token");
            $statement->execute(['token' => $token]);
            $data = $statement->fetch(PDO::FETCH_ASSOC);

            if (!empty($data)) {
                $created_at_datetime = DateTime::createFromFormat('Y-m-d H:i:s', $data['expiration_time']);
                $current_time = new DateTime();


                if (($current_time->getTimestamp() > $created_at_datetime->getTimestamp()) ) {
                    // Токен устарел
                    $statement = $connection->prepare('DELETE FROM user_tokens WHERE token = :token');
                    $statement->execute(['token' => $token]);
                    http_response_code(400);
                    echo 'Error: Access to change password denied';
                } else {
                    // Срок жизни токена не истек
                    $statement = $connection->prepare('UPDATE users SET password = :password WHERE id = :id');
                    $statement->execute(['password' => $password, 'id' => $data['user_id']]);
                    // Возвращаем успешный ответ
                    header('HTTP/1.1 200 OK');
                    header('Content-Type: application/json');
                    echo 'Password changed successfully';
                }
            } else {
                http_response_code(400);
                echo 'Error: Access to change password denied';
                return;
            }
        }
    }
}
