<?php
declare(strict_types=1);

namespace Classes\Services;

use Classes\Tasks\Dto\MailMessage;
use PDO;

class MailQueueService
{
    private string $tableQueue;

    public function __construct(private PDO $pdo, string $tableQueue)
    {
        $this->tableQueue = $tableQueue;
    }

    public function getPending(): iterable
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->tableQueue} WHERE status = 'pending'");

        foreach ($stmt as $row) {
            $recipients = json_decode($row['recipient'], true) ?? [];
            $ccList = json_decode($row['carbon_copy'], true) ?? [];
        
            // body JSON -> trasformiamo in HTML
            $bodyData = [];
            if (!empty($row['body'])) {
                $bodyData = json_decode($row['body'], true) ?: [];
            }
        
            $bodyHtml = buildHtmlBody(
                $bodyData,
                $row['application'] ?? '',
                $row['alert_message'] ?? null,
                $row['alert_type'] ?? 'info',
                'logo_comune', // logoCid, puoi lasciare default
                $row['institutional_name'] ?? ''
            );
        
            yield new MailMessage(
                to: $recipients,
                cc: $ccList,
                subject: $row['subject'] ?? '',
                bodyHtml: $bodyHtml,
                attachments: json_decode($row['attachments'] ?? '[]', true) ?: [],
                id: (int)$row['id'] 
            );
        }        
    }
}
