<?php

namespace App\Service;

use App\Exception\MailerException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class EmailService
{
    public function __construct(private readonly MailerInterface $mailer) {}

    /**
     * Send email with file attachment from disk
     * @throws MailerException
     * @throws TransportExceptionInterface
     */
    public function sendWithFileAttachment(
        string $from,
        string $to,
        string $subject,
        string $body,
        string $filePath,
        ?string $attachmentName = null
    ): void {
        if (!file_exists($filePath)) {
            throw new MailerException("Attachment file not found: $filePath");
        }

        $attachmentName = $attachmentName ?? basename($filePath);

        $email = new Email()
            ->from(new Address($from, $from))
            ->to($to)
            ->subject($subject)
            ->html($body)
            ->attachFromPath($filePath, $attachmentName, 'text/csv');

        $this->mailer->send($email);
    }
}
