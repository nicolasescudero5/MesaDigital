<?php

declare(strict_types=1);

namespace App\Services;

use Psr\Log\LoggerInterface;

class PortalMailerAdapter implements MailerInterface
{
    private ?string $lastError = null;

    public function __construct(
        private ?LoggerInterface $logger = null
    ) {}

    public function send(string $to, string $subject, string $htmlBody, ?string $altBody = null): bool
    {
        $socketLabsFile = dirname(__DIR__, 3) . '/portal/app/core/SocketLabsMailer.php';
        if (file_exists($socketLabsFile)) {
            require_once $socketLabsFile;
        }

        if (class_exists('\\SocketLabsMailer')) {
            $options = [
                'from_name' => 'Mesa Digital — Red Itínere',
            ];
            $res = \SocketLabsMailer::send($to, $subject, $htmlBody, $options);
            if (!empty($res['success'])) {
                $this->lastError = null;
                return true;
            }
            $this->lastError = $res['message'] ?? 'SocketLabs error';
            if ($this->logger) {
                $this->logger->error("Error enviando email vía Portal SocketLabsMailer a {$to}: " . $this->lastError);
            }
            return false;
        }

        $this->lastError = 'SocketLabsMailer no se encontró en portal/app/core/SocketLabsMailer.php';
        if ($this->logger) {
            $this->logger->error($this->lastError);
        }
        return false;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
