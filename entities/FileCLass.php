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
                            $drPath = $_SERVER['DOCUMENT_ROOT'] . '/files/' . $user['id'] . '/';
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


                    if ($folderName == NULL) {
                        $file_path = $user['id'] . '/' . $file['file_name'];
                    } else {
                        $file_path = $folderName['folder_name'] . '/' . $file['file_name'];
                    }

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

                $pathFolder = $_SERVER['DOCUMENT_ROOT'] . $folderPath . $folderName . '/';
                global $connection;
                $connection->beginTransaction();
                $statement = $connection->prepare('SELECT id FROM folders WHERE folder_name = :folder_name');
                $statement->execute(['folder_name' => $user['id']]);
                $folderId = $statement->fetch(PDO::FETCH_ASSOC);
                $folderId = $folderId['id'];
                $statement = $connection->prepare('INSERT INTO folders (id, folder_name, user_id, parent_folder_id, created_at, updated_at) values(NULL, :folder_name, :user_id, :parent_folder_id, DEFAULT, DEFAULT)');
                $statement->execute(['folder_name' => $folderName, 'user_id' => $user['id'], 'parent_folder_id' => $folderId]);
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
                        echo json_encode(['files' => $arrFiles]);
                    }
                } else {
                    echo json_encode(['message' => 'Folder contains no files']);
                }
            } catch (PDOException $e) {
                echo json_encode(['Error' => $e->getMessage()]);
            }
        }
    }

    //Переименовать папку 
    static public function renameFolder()
    {
        header('Content-Type: application/json');
        $user = self::userAuthorization();
        if ($user) {
            $data = json_decode(file_get_contents("php://input"), true);
            if (isset($data['file_id']) && !empty(trim($data['file_id'])) && isset($data['folder_name']) && !empty($data['folder_name'])) {
                $fileId = htmlspecialchars(trim($data['file_id']));
                $newFileName = htmlspecialchars(trim($data['folder_name']));
                global $connection;
                $statement = $connection->prepare('SELECT folder_name FROM folders WHERE id =:id AND user_id = :user_id');
                $statement->execute(['id' => $fileId, 'user_id' => $user['id']]);
                $fileName = $statement->fetch(PDO::FETCH_ASSOC);
                if (!empty($fileName)) {
                    try {
                        global $connection;
                        //Переименовываем папку
                        $connection->beginTransaction();

                        $statement = $connection->prepare('UPDATE folders SET folder_name = :new_folder_name WHERE id =:id AND user_id = :user_id');
                        $statement->execute(['id' => $fileId, 'new_folder_name' => $newFileName, 'user_id' => $user['id']]);

                        try {
                            if (rename($_SERVER['DOCUMENT_ROOT'] . '/files/' . $user['id'] . '/' . $fileName['folder_name'], $_SERVER['DOCUMENT_ROOT'] . '/files/' . $user['id'] . '/' . $newFileName)) {
                                $connection->commit();
                                http_response_code(200);
                                echo json_encode(['message' => 'Folder renamed success']);
                            } else {
                                http_response_code(400);
                                echo json_encode(['Error' => 'Folder is not renamed']);
                            }
                        } catch (PDOException $e) {
                            // Откатываем транзакцию в случае ошибки
                            $connection->rollBack();
                            http_response_code(400);
                            echo json_encode(['Error' => 'Folder is not renamed']);
                        }
                    } catch (PDOException $e) {
                        http_response_code(400);
                        echo json_encode(['Error' => 'Folder root is not exists']);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'File is not found']);
                }
            }
        }
    }

    //Переименовать или переместить файл
    static public function renameMoveFile()
    {
        header('Content-Type: application/json');
        $user = self::userAuthorization();
        if ($user) {
            $data = json_decode(file_get_contents("php://input"), true);
            if (isset($data['file_id']) && !empty(trim($data['file_id']))) {
                $fileId = htmlspecialchars(trim($data['file_id']));
                //Переименовываем файл
                if (isset($data['file_name']) && !empty(trim($data['file_name'])) && !isset($data['new_folder_id'])) {
                    $newFileName = htmlspecialchars(trim($data['file_name']));
                    self::renameFile($fileId, $newFileName, $user);

                    //Перемещаем файл
                } elseif (!empty($data['new_folder_id']) && !empty($data['file_id']) && !isset($data['file_name'])) {
                    $fileId = htmlspecialchars(trim($data['file_id']));
                    $newParentFolderId = htmlspecialchars(trim($data['new_folder_id']));
                    self::moveFile($fileId, $newParentFolderId, $user);
                } else {
                    echo json_encode(['error' => 'Bad request']);
                }
            }
        }
    }

    static private function renameFile($fileId, $newFileName, $user)
    {
        $fileName = $user['id'] . '_' . time() . '_' . $newFileName;
        try {
            global $connection;
            //Узнаем id папки в которой лежит файл
            $statement = $connection->prepare('SELECT file_name, parent_folder_id, file_type FROM files WHERE id = :id AND user_id = :user_id');
            $statement->execute(['id' => $fileId, 'user_id' => $user['id']]);
            $data = $statement->fetch(PDO::FETCH_ASSOC);
            $parentFolderId = $data['parent_folder_id'];
            $oldFileName = $data['file_name'];
            $fileType = $data['file_type'];
            $mime_parts = explode('/', $fileType);
            $fileType = end($mime_parts);

            //Узнаем название папки
            $statement = $connection->prepare('SELECT folder_name FROM folders WHERE id = :id');
            $statement->execute(['id' => $parentFolderId]);
            $parentFolderName = $statement->fetch(PDO::FETCH_ASSOC);

            $filePath = $_SERVER['DOCUMENT_ROOT'] . '/files/' . $user['id'] . '/' . $parentFolderName['folder_name'] . '/';
            try {
                $connection->beginTransaction();
                $statement = $connection->prepare('UPDATE files SET file_name = :new_file_name, original_name = :new_original_name WHERE id =:id AND user_id = :user_id');
                $statement->execute(['new_file_name' => $fileName . '.' . $fileType, 'new_original_name' => $newFileName . '.' . $fileType, 'id' => $fileId, 'user_id' => $user['id']]);

                $oldFile = $filePath . $oldFileName;
                $newFile = $filePath . $fileName . '.' . $fileType;

                if (rename($oldFile, $newFile)) {
                    $connection->commit();
                    http_response_code(200);
                    echo json_encode(['message' => 'Success rename file']);
                } else {
                    $connection->rollBack();
                    http_response_code(400);
                    echo json_encode(['error' => 'Unsuccess rename file']);
                }
            } catch (PDOException $e) {
                echo json_encode(['Error' => $e->getMessage()]);
            }
        } catch (PDOException $e) {
            echo json_encode(['Error' => $e->getMessage()]);
        }
    }

    static private function moveFile($fileId, $newParentFolderId, $user)
    {
        global $connection;
        $statement = $connection->prepare('SELECT file_name, parent_folder_id FROM files WHERE id = :id AND user_id = :user_id');
        $statement->execute(['id' => $fileId, 'user_id' => $user['id']]);
        $dataFile = $statement->fetch(PDO::FETCH_ASSOC);

        $oldParentId = $dataFile['parent_folder_id'];

        $statement = $connection->prepare('SELECT folder_name FROM folders WHERE id = :id AND user_id = :user_id');
        $statement->execute(['id' => $oldParentId, 'user_id' => $user['id']]);
        $dataOldFolder =  $statement->fetch(PDO::FETCH_ASSOC);

        $statement = $connection->prepare('SELECT folder_name FROM folders WHERE id = :id AND user_id = :user_id');
        $statement->execute(['id' => $newParentFolderId, 'user_id' => $user['id']]);
        $dataNewFolder = $statement->fetch(PDO::FETCH_ASSOC);

        $fileName = $dataFile['file_name'];
        $oldParentFolderName = $dataOldFolder['folder_name'];
        $newParentFolderName = $dataNewFolder['folder_name'];

        try {
            $connection->beginTransaction();
            $statement = $connection->prepare('UPDATE files SET parent_folder_id = :parent_folder_id WHERE id = :id AND user_id = :user_id');
            $statement->execute(['parent_folder_id' => $newParentFolderId, 'id' => $fileId, 'user_id' => $user['id']]);

            $oldFilePath = $_SERVER['DOCUMENT_ROOT'] . '/files/' . $user['id'] . '/' . $oldParentFolderName . '/' . $fileName;
            $newFilePath = $_SERVER['DOCUMENT_ROOT'] . '/files/' . $user['id'] . '/' . $newParentFolderName . '/' . $fileName;

            if (rename($oldFilePath, $newFilePath)) {
                $connection->commit();
                http_response_code(200);
                echo json_encode(['message' => 'Success move file']);
            } else {
                $connection->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Unsuccess move file']);
            }
        } catch (PDOException $e) {
            $connection->rollBack();
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    //Добавить доступ к файлу пользователю
    static public function shareFale()
    {
        header('Content-Type: application/json');
        $user = self::userAuthorization();
        if ($user) {
            $url = $_SERVER['REQUEST_URI'];
            $parsed_url = parse_url($url);

            $userId = intval(explode('/', $parsed_url['path'])[3]);
            $fileId = intval(explode('/', $parsed_url['path'])[4]);

            try {
                global $connection;
                $statement = $connection->prepare('SELECT email FROM users WHERE id =:id');
                $statement->execute(['id' => $userId]);
                $dataUserId = $statement->fetch(PDO::FETCH_ASSOC);
                if ($dataUserId) {
                    $statement = $connection->prepare('SELECT file_name FROM files WHERE id =:id');
                    $statement->execute(['id' => $fileId]);
                    $dataFileId = $statement->fetch(PDO::FETCH_ASSOC);
                    if ($dataFileId) {
                        $statement = $connection->prepare('INSERT INTO file_share_permissions (id, file_id, user_id) values(NULL, :file_id, :user_id)');
                        $statement->execute(['file_id' => $fileId, 'user_id' => $userId]);
                        http_response_code(200);
                    } else {
                        http_response_code(404);
                        echo json_encode(['error' => 'File is not found']);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'User is not found']);
                }
            } catch (PDOException $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
    }

    //Получить список пользователей имеющих доступ к файлу
    static public function shareUsers()
    {
        header('Content-Type: application/json');
        $user = self::userAuthorization();
        if ($user) {
            $url = $_SERVER['REQUEST_URI'];
            $parts = explode('/', $url);
            $fileId = $parts[sizeof($parts) - 1]; // Извлекаем id файла из URL

            try {
                global $connection;
                $statement = $connection->prepare('SELECT user_id FROM file_share_permissions WHERE file_id = :file_id');
                $statement->execute(['file_id' => $fileId]);
                $data = $statement->fetchAll();
                $userIds = array_column($data, 'user_id');
                http_response_code(200);
                echo json_encode(['user_id' => $userIds]);
            } catch (PDOException $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
    }

    //Прекратить доступ к файлу 
    static public function terminateAccessFile()
    {
        header('Content-Type: application/json');
        $user = self::userAuthorization();
        if ($user) {
            $url = $_SERVER['REQUEST_URI'];
            $parsed_url = parse_url($url);

            $userId = intval(explode('/', $parsed_url['path'])[3]);
            $fileId = intval(explode('/', $parsed_url['path'])[4]);
            try {
                global $connection;
                $statement = $connection->prepare('DELETE FROM file_share_permissions WHERE file_id = :file_id AND user_id = :user_id');
                $statement->execute(['file_id' => $fileId, 'user_id' => $userId]);

                $rowCount = $statement->rowCount();
                if ($rowCount > 0) {
                    http_response_code(200);
                    echo json_encode(['message' => 'File access terminated successfully.']);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Failed to terminate file access.']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
    }
}
