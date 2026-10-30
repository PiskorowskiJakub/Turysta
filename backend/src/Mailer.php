<?php
declare(strict_types=1);

namespace Turysta;

use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

class Mailer
{
    public function send(string $to, string $subject, string $text): bool
    {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Timeout = 5;
            $mail->Host = getenv('MAIL_HOST') ?: 'mailpit';
            $mail->Port = (int) (getenv('MAIL_PORT') ?: 1025);
            $mail->SMTPAuth = false;
            $mail->SMTPAutoTLS = false;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(getenv('MAIL_FROM') ?: 'no-reply@turysta.local', 'Turysta');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $text;
            $mail->isHTML(false);
            $mail->send();
            return true;
        } catch (Throwable $e) {
            error_log('Mailer: ' . $e->getMessage());
            return false;
        }
    }
}
