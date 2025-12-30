<?php
declare(strict_types=1);

namespace Classes\Tasks\Dto;

class MailMessage {
    public ?int $id;
    public array $to;
    public array $cc;
    public string $subject;
    public string $bodyHtml;
    public array $attachments;

    public function __construct(
        array $to,
        array $cc = [],
        string $subject = '',
        string $bodyHtml = '',
        array $attachments = [],
        ?int $id = null
    ) {
        $this->to = $to;
        $this->cc = $cc;
        $this->subject = $subject;
        $this->bodyHtml = $bodyHtml;
        $this->attachments = $attachments;
        $this->id = $id;
    }
}

