<?php

namespace SecureAuth\Jobs;
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use SecureAuth\Jobs\JobInterface;

//Load Composer's autoloader (created by composer, not included with PHPMailer)
require 'vendor/autoload.php';

class EmailJob implements JobInterface
{
    public function handle(array $configuration, array $payload)
    {
        if (!isset($payload['attachment']) || empty($payload['attachment'])) {
            return $this->sendMail($configuration, $payload);
        }
        return $this->sendMailWithAttachment($configuration, $payload);
    }

    // Send email without attachment
    private function sendMail(array $configuration, array $payload)
    {
        $mail = new PHPMailer(true);

        try {
            $this->configureMailer($mail, $configuration);

            //sender information
            $mail->setFrom(
                $configuration['MAIL']['GOOGLE_EMAIL'],
                $configuration['MAIL']['PROJECT_NAME']
            );
            //Recipients
            $mail->addAddress($payload['to'], '');

            //Content
            $mail->isHTML(true);
            $mail->Subject = $payload['subject'];
            $mail->Body    = $payload['body'];

            $mail->send();
            return true;
        } catch (Exception $e) {
            return $mail->ErrorInfo;
        }
    }

    // Send email with attachment
    private function sendMailWithAttachment(array $configuration, array $payload)
    {
        $mail = new PHPMailer(true);

        try {
            $this->configureMailer($mail, $configuration);

            //sender information
            $mail->setFrom(
                $configuration['MAIL']['GOOGLE_EMAIL'],
                $configuration['MAIL']['PROJECT_NAME']
            );
            //Recipients
            $mail->addAddress($payload['to'], '');

            $filePath = realpath($payload['attachment']);
            if (!$filePath) {
                throw new \Exception("Attachment path invalid: " . $payload['attachment']);
            }
            if (!file_exists($filePath)) {
                throw new \Exception("Attachment not found: " . $filePath);
            }
            if (!is_readable($filePath)) {
                throw new \Exception("Attachment not readable: " . $filePath);
            }

            $fileName = basename($filePath);


            // let PHPMailer detect MIME type itself (safer)
            $mail->addAttachment($filePath, $fileName);
            //Content
            $mail->isHTML(true);
            $mail->Subject = $payload['subject'];
            $mail->Body    = $payload['body'];

            $mail->send();
            return true;
        } catch (Exception $e) {
            return $mail->ErrorInfo;
        }
    }

    // Shared config logic
    private function configureMailer(PHPMailer $mail, array $configuration)
    {
        $mail->SMTPDebug = 0; // change to SMTP::DEBUG_SERVER for full logs
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $configuration['MAIL']['GOOGLE_EMAIL'];
        $mail->Password   = $configuration['MAIL']['GOOGLE_SECRET_KEY'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
    }
}
