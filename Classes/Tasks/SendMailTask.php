<?php
declare(strict_types=1);

namespace Classes\Tasks;

use Classes\Services\MailService;
use Classes\Services\Logger;
use Classes\Tasks\Dto\MailMessage;

class SendMailTask
{
    public function __construct(
        private MailService $mailer,
        private Logger $logger
    ) {
        $this->logger->setTask('send_mail');
    }

    /**
     * Invio singola mail
     */
    public function send(MailMessage $message): bool
    {
        try {
            $this->validate($message);

            $this->mailer->send([
                'to'           => $message->to,
                'cc'           => $message->cc,
                'subject'      => $message->subject,
                'body_html'    => $message->bodyHtml,
                'attachments'  => $message->attachments,
            ]);

            $this->logger->info("Mail inviata: {$message->subject}");
            return true;

        } catch (\Throwable $e) {
            $this->logger->error("Invio mail fallito: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Invio batch
     */
    public function sendBatch(iterable $messages): void
    {
        foreach ($messages as $message) {
            $this->send($message);
        }
    }

    /* ================= PRIVATE ================= */

    private function validate(MailMessage $message): void
    {
        if (empty($message->to)) {
            throw new \InvalidArgumentException('Destinatari TO mancanti');
        }

        foreach (array_merge($message->to, $message->cc) as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Email non valida: {$email}");
            }
        }
    }
}
