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
                    $folderId = htmlspecialchars(trim($_POST['folder_id']));

                    if (!empty($folderId)) {
                        global $connection;
                        $statement = $connection->prepare('SELECT id, folder_name FROM folders WHERE id = :folder_id AND user_id = :user_id');
                        $statement->execute(['folder_id' => $folderId, 'user_id' => $user['id']]);
                        $infoFolder = $statement->fetch(PDO::FETCH_ASSOC);
                        if ($folderId == $user['id']) {
                            $drPath = $_SERVER['DOCUMENT_ROOT'] . '/files/' . $infoFolder['folder_name'] . '/';
                        } else {
                            $drPath = $_SERVER['DOCUMENT_ROOT'] . '/files/' . $user['id'] . '/' . $infoFolder['folder_name'] . '/';
                        }
                        if (is_dir($drPath)) {
                            self::addFileFolder($user, $drPath, $infoFolder);
                        } else {
                            echo json_encode(['Error' => 'Folder is not exists']);
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(['Error' => 'Bad request']);
                        return false;
                    }
                }
            }
        } else {
            http_response_code(400);
            echo json_encode(['Error' => 'Access denied']);
            return false;
        }
    }

    //Добавление файла
    static private function addFileFolder($user, $drPath, $infoFolder)
    {
        $fileName = $user['id'] . '_' . time() . '_' . $_FILES['file']['name'];
        $originalFileName = $_FILES['file']['name'];
        $fileSize = $_FILES['file']['size'];
        $fileType = $_FILES['file']['type'];
        try {
            global $connection;
            $connection->beginTransaction();
            $statement = $connection->prepare('INSERT INTO files (id, parent_folder_id, original_name, user_id, file_name, file_size, file_type, file_created_at) values(NULL, :parent_folder_id, :original_name, :user_id, :file_name, :file_size, :file_type, DEFAULT)');
            $statement->execute(['parent_folder_id' => $infoFolder['id'], 'user_id' => $user['id'], 'file_name' => $fileName, 'original_name' => $originalFileName, 'file_size' => $fileSize, 'file_type' => $fileType]);

            $sourcePath = $_FILES['file']['tmp_name']; //временный путь до файла на сервере
            //перемещаем файл из временной директории в папку uploads
            $filePathName = $drPath . $fileName;
            if (move_uploaded_file($sourcePath, $filePathName)) {
                $connection->commit();
                echo 'Файл успешно загружен';
            } else {
                $connection->rollBack();
                echo 'File upload error';
                echo 'Error details: ' . error_get_last();
            }
        } catch (PDOException $e) {
            $connection->rollBack();
            echo "File upload error: " . $e->getMessage();
        }
    }

    //Выбор метода в зависимости от присутствия параметров
    static public function showFile()
    {
        header('Content-Type: application/json');
        $user = self::userAuthorization();
        if ($user) {
            $user_id = $user['id'];
            $url = $_SERVER['REQUEST_URI'];
            $parts = explode('/', $url);
            $file_id = $parts[sizeof($parts) - 1]; // Извлекаем id файла из URL
            //Проверка на присутствие параметра в URL
            if (empty($file_id)) {
                self::showListFiles($user_id);
            } else {
                self::getFileId($user_id, $file_id);
            }
        }
    }

    //Вывести список всех файлов определенного пользователя
    static private function showListFiles($user_id)
    {
        global $connection;
        try {
            $statement = $connection->prepare('SELECT file_name FROM files WHERE user_id = :user_id');
            $statement->execute(['user_id' => $user_id]);
            $listFiles = $statement->fetchAll(PDO::FETCH_ASSOC);
            $fileNames = array_column($listFiles, 'file_name');
            if (count($fileNames) > 0) {
                $fileNames = array_combine(range(0, count($fileNames) - 1), $fileNames);
            } else {
                $fileNames = array();
            }
            $connection = null;
            echo json_encode(['files' => $fileNames]);
        } catch (PDOException $e) {
            echo 'Error request to Data Base: ' . $e->getMessage();
            $connection = null;
            http_response_code(500);
        }
    }

    //Получение информации о файле по id
    static private function getFileId($user_id, $file_id)
    {
        global $connection;
        try {
            $statement = $connection->prepare('SELECT file_name, file_size, file_created_at, file_type FROM files WHERE user_id = :user_id AND id = :id');
            $statement->execute(['user_id' => $user_id, 'id' => $file_id]);
            $file = $statement->fetch(PDO::FETCH_ASSOC);
            if (empty($file)) {
                echo json_encode(['error' => 'File is not found']);
                return;
                $connection = null;
            };
            $connection = null;
            echo json_encode(['file' => $file]);
        } catch (PDOException $e) {
            echo 'Error request to Data Base: ' . $e->getMessage();
            http_response_code(500);
        }
    }

    //Удалить файл по id
    static public function deleteFile()
    {
        header('Content-Type: application/json');
        $user = self::userAuthorization();
        if ($user) {
            $user_id = $user['id'];
            $url = $_SERVER['REQUEST_URI'];
            $parts = explode('/', $url);
            $file_id = $parts[sizeof($parts) - 1]; // Извлекаем id файла из URL
            if (!empty($file_id)) {
                try {
                    // Начинаем транзакцию
                    global $connection;
                    $connection->beginTransaction();
                    $statement = $connection->prepare('SELECT parent_folder_id, file_name FROM files WHERE id = :file_id AND user_id = :user_id');
                    $statement->execute(['file_id' => $file_id, 'user_id' => $user_id]);
                    $file = $statement->fetch(PDO::FETCH_ASSOC);

                    $statement = $connection->prepare('SELECT folder_name FROM folders WHERE id = :file_id AND user_id = :user_id');
                    $statement->execute(['file_id' => $file['parent_folder_id'], 'user_id' => $user_id]);
                    $folderName = $statement->fetch(PDO::FETCH_ASSOC);

                    $file_path = $folderName['folder_name'] . '/' . $file['file_name'];
                    if ($file_path) {
                        if (unlink($_SERVER['DOCUMENT_ROOT'] . '/files/' . $file_path)) {
                            $statement = $connection->prepare('DELETE FROM files WHERE id = :file_id AND user_id = :user_id');
                            $statement->execute(['file_id' => $file_id, 'user_id' => $user_id]);
                            // Фиксируем изменения в БД
                            $connection->commit();
                            http_response_code(204);
                            $connection = null;
                        } else {
                            echo json_encode(['error' => 'An error occurred while deleting the file.']);
                        }
                    } else {
                        http_response_code(404);
                        echo json_encode(['error' => 'File not found']);
                    }
                } catch (PDOException $e) {
                    // Откатываем транзакцию в случае ошибки
                    $connection->rollBack();
                    echo json_encode(['error' => 'An error occurred while deleting the file.']);
                }
            } else {
                echo json_encode(['error' => 'Not all data was transferred']);
            }
        }
    }

    //Добавить папку
    static public function addFolder()
    {
        header('Content-Type: application/json');
        $user = self::userAuthorization();
        if ($user) {
            $data = json_decode(file_get_contents("php://input"), true);
            if (isset($data['folder_name']) && !empty($data['folder_name'])) {
                $folderName = trim(htmlspecialchars($data['folder_name']));
                $folderPath = '/files/' . $user['id'] . '/';
                var_dump($folderPath);
                try {
                    global $connection;
                    //Добавляем папку
                    $connection->beginTransaction();
                    $statement = $connection->prepare('INSERT INTO folders (id, folder_name, user_id, parent_folder_id, created_at, updated_at) values(NULL, :folder_name, :user_id, :parent_folder_id, DEFAULT, DEFAULT)');
                    $statement->execute(['folder_name' => $folderName, 'user_id' => $user['id'], 'parent_folder_id' => $user['id']]);
                    $pathFolder = $_SERVER['DOCUMENT_ROOT'] . $folderPath . '/' . $folderName . '/';
                    try {
                        if (!file_exists($pathFolder)) { // если папка не существует
                            mkdir($pathFolder, 0777, true); // создаем ее со всеми правами доступа
                            $connection->commit();
                            http_response_code(201);
                            echo json_encode(['name' => $folderName, 'path' => $folderPath]);
                        }
                    } catch (PDOException $e) {
                        // Откатываем транзакцию в случае ошибки
                        $connection->rollBack();
                        echo json_encode(['Error' => 'Folder is not created']);
                    }
                } catch (PDOException $e) {
                    echo json_encode(['Error' => 'Folder root is not exists']);
                }
            } else {
                echo json_encode(['Error' => 'Parameters passed incorrectly']);
            }
        }
    }


    //Получить список файлов папки
    static public function getInfoFolder()
    {
        header('Content-Type: application/json');
        $user = self::userAuthorization();
        if ($user) {
            $url = $_SERVER['REQUEST_URI'];
            $parts = explode('/', $url);
            $folder_id = $parts[sizeof($parts) - 1]; // Извлекаем id файла из URL
            if (empty($folder_id)) {
                echo json_encode(['Error' => 'Params ID folder is empty']);
                return;
            }
            //Получаем файлы у которых родительская папка с нужным id
            try {
                global $connection;
                $statement = $connection->prepare('SELECT original_name FROM files WHERE parent_folder_id = :id');
                $statement->execute(['id' => $folder_id]);
                $data = $statement->fetchAll();
                $arrFiles = [];
                if (!empty($data)) {
                    foreach ($data as $row) {
                        $arrFiles[] = $row['original_name'];
                        echo json_encode(['Files' => $arrFiles]);
                    }
                } else {
                    echo json_encode(['message' => 'Folder contains no files']);
                }
            } catch (PDOException $e) {
                echo json_encode(['Error' => $e->getMessage()]);
            }
        }
    }
}
