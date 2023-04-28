<?php

class FileClass
{

    //Проверка авторизации пользователя
    static private function userAuthorization()
    {
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
                return false;
            } else {
                // Срок жизни токена не истек
                $user_id = $data['user_id'];
                //Достаем данные пользователя
                $statement = $connection->prepare('SELECT id, email FROM users WHERE id = :id');
                $statement->execute(['id' => $user_id]);
                $data = $statement->fetch(PDO::FETCH_ASSOC);

                if (!empty($data)) {
                    return $data;
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'User is not found']);
                    return;
                }
                $connection = null;
            }
        } else {
            http_response_code(400);
            echo 'Error: Access denied';
            return false;
        }
    }

    //Добавить файл 
    static public function addFile()
    {
        //Проверяем токен авторизации
        $user = self::userAuthorization();
        if ($user) {
            if (!in_array($_FILES['file']['type'], ['image/jpeg', 'image/png', 'application/pdf'])) {
                echo 'Wrong file type! The file can be pdf, png, jpeg.';
            } else {
                if ($_FILES['file']['size'] > 2147483648) {
                    echo 'File size should not exceed 2 GB';
                    return;
                } else {
                    $fileName = $user['id'] . '_' . time() . '_' . $_FILES['file']['name'];
                    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/files/' . $user['id'] . '/';
                    $fileSize = $_FILES['file']['size'];
                    $fileType = $_FILES['file']['type'];

                    global $connection;
                    $statement = $connection->prepare('INSERT INTO files (id, user_id, file_name, file_path, file_size, file_type, file_created_at) values(NULL, :user_id, :file_name, :file_path, :file_size, :file_type, DEFAULT)');
                    $statement->execute(['user_id' => $user['id'], 'file_name' => $fileName, 'file_path' => $filePath, 'file_size' => $fileSize, 'file_type' => $fileType]);
                    //Переносим файл при успешном добавлении мета-данных в БД
                    if ($statement) {
                        $userFolder = $filePath;
                        if (!file_exists($userFolder)) { // если папка не существует
                            mkdir($userFolder, 0777, true); // создаем ее со всеми правами доступа
                        }
                        $sourcePath = $_FILES['file']['tmp_name']; //временный путь до файла на сервере
                        $targetPath = $userFolder . $fileName; //путь до файла, где его нужно сохранить
                        //перемещаем файл из временной директории в папку uploads

                        if (move_uploaded_file($sourcePath, $targetPath)) {
                            echo 'Файл успешно загружен';
                        } else {
                            echo 'File upload error';
                            echo 'Error details: ' . error_get_last(); // выводим подробную
                        }
                    } else {
                        echo 'File upload error';
                    }
                }
            }
        }
    }

    //Вывести список файлов
    static public function showListFiles()
    {            
        header('Content-Type: application/json');
        $user = self::userAuthorization();
        if ($user) {
            $user_id = $user['id'];
            global $connection;
            try {
                $statement = $connection->prepare('SELECT file_name FROM files WHERE user_id = :user_id');
                $statement->execute(['user_id' => $user_id]);
                $listFiles = $statement->fetchAll(PDO::FETCH_ASSOC);
                $fileNames = array_column($listFiles, 'file_name');
                $fileNames = array_combine(range(0, count($fileNames) - 1), $fileNames); // добавлены числовые ключи
                echo json_encode(['files' => $fileNames]);
            } catch (PDOException $e) {
                echo 'Error request to Data Base: ' . $e->getMessage();
                http_response_code(500);
            }
        }
    }
}
