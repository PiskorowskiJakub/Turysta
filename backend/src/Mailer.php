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
            $mail->Timeout = 10;
            $mail->Host = getenv('MAIL_HOST') ?: 'mailpit';
            $mail->Port = (int) (getenv('MAIL_PORT') ?: 1025);
            // Lokalnie (Mailpit) bez logowania i szyfrowania; na produkcji MAIL_USER/MAIL_PASSWORD oraz MAIL_ENCRYPTION=tls|ssl.
            $user = (string) (getenv('MAIL_USER') ?: '');
            $mail->SMTPAuth = $user !== '';
            if ($user !== '') {
                $mail->Username = $user;
                $mail->Password = (string) (getenv('MAIL_PASSWORD') ?: '');
            }
            $encryption = strtolower((string) (getenv('MAIL_ENCRYPTION') ?: ''));
            $mail->SMTPAutoTLS = false;
            if ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(getenv('MAIL_FROM') ?: 'no-reply@dwadukaty.pl', 'Dwa Dukaty');
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
