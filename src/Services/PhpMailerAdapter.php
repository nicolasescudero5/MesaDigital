<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Psr\Log\LoggerInterface;

class PhpMailerAdapter implements MailerInterface
{
    private ?string $lastError = null;

    public function __construct(
        private array $config,
        private ?LoggerInterface $logger = null
    ) {}

    public function send(string $to, string $subject, string $htmlBody, ?string $altBody = null): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host = $this->config['host'] ?? '127.0.0.1';
            $mail->Port = (int)($this->config['port'] ?? 1025);
            $mail->CharSet = 'UTF-8';

            if (!empty($this->config['username'])) {
                $mail->SMTPAuth = true;
                $mail->Username = $this->config['username'];
                $mail->Password = $this->config['password'] ?? '';
            } else {
                $mail->SMTPAuth = false;
            }

            if (!empty($this->config['encryption']) && $this->config['encryption'] !== 'null') {
                $mail->SMTPSecure = $this->config['encryption'];
            }

            // Remitente y destinatario
            $fromAddress = $this->config['from_address'] ?? 'no-responder@reditinere.com';
            $fromName = $this->config['from_name'] ?? 'Mesa Digital — Red Itínere';
            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($to);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            if ($altBody) {
                $mail->AltBody = $altBody;
            } else {
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));
            }

            $mail->send();
            $this->lastError = null;
            return true;
        } catch (Exception $e) {
            $this->lastError = $mail->ErrorInfo;
            if ($this->logger) {
                $this->logger->error("Error enviando email a {$to}: " . $mail->ErrorInfo);
            }
            return false;
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
