<?php

require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


// отправляем сообщение на указанный email
if (isset($_POST['token']) && isset($_POST['email'])) {
    $token = $_POST['token'];
    // шаблон сообщения с ссылкой на страницу восстановления пароля
    $message = "Для восстановления пароля перейдите по ссылке: <a href='http://cloud.storage.local/resetpassword.php?token=" . urlencode($token) . "'>Восстановить пароль</a>";

    $mail = new PHPMailer();
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        try {
            //$mail->SMTPDebug = 4;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom('');
            $mail->addAddress($_POST['email']);
            $mail->isHTML(true);
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.yandex.ru';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = '';                     //SMTP username
            $mail->Password   = '';
            $mail->Subject = 'Восстановление пароля: cloud.storage';
            $mail->Port       = 465;
            $mail->SMTPSecure = 'ssl';            //Enable implicit TLS encryption
            $mail->Body = $message;

            if ($mail->send()) {
                $response = array('status' => 'success', 'message' => 'A password recovery link has been sent to your email');
                echo json_encode($response);
            } else {
                throw new Exception('Error send mail!');
            }
        } catch (Exception $error) {
            $myError = $error->getMessage();
        }
    }
}
