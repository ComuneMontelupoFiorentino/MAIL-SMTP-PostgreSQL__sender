<?php
declare(strict_types=1);

namespace Classes\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private array $config;
    private PHPMailer $mailer;

    public function __construct(
        string $env,
        string $configFile,
        private Logger $logger
    ) {
        $this->config = $this->loadConfig($configFile, $env);
        $this->mailer = $this->createMailer();
    }

    public function send(array $mail): bool
    {
        try {
            // Pulizia mail precedente
            $this->mailer->clearAllRecipients();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders(); // opzionale

            // Aggiungi logo come immagine incorporata
            $logoPath = __DIR__ . '/../../img/logo.png';
            if (!file_exists($logoPath)) {
                throw new \RuntimeException("Logo non trovato in $logoPath");
            }
            $this->mailer->addEmbeddedImage($logoPath, 'logo_comune');

            // Impostazioni principali
            $this->mailer->Subject = $mail['subject'] ?? '';
            $this->mailer->Body    = $mail['body_html'] ?? '';
            $this->mailer->isHTML(true);

            // Destinatari
            foreach ($mail['to'] as $to) {
                $this->mailer->addAddress($to);
            }

            // CC
            foreach ($mail['cc'] ?? [] as $cc) {
                $this->mailer->addCC($cc);
            }

            // Allegati
            foreach ($mail['attachments'] ?? [] as $file) {
                if (file_exists($file)) {
                    $this->mailer->addAttachment($file);
                }
            }

            // Invio
            $this->mailer->send();
            $this->logger->info("Email inviata: {$mail['subject']}");

            return true;

        } catch (Exception $e) {
            $this->logger->error("Errore invio mail: " . $e->getMessage());
            return false;
        }
    }

    /* ================= PRIVATE ================= */

    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = $this->config['host'];
        $mail->SMTPAuth   = filter_var($this->config['auth'], FILTER_VALIDATE_BOOLEAN);
        $mail->SMTPSecure = $this->config['secure'];
        $mail->Port       = (int)$this->config['port'];
        $mail->Username   = $this->config['user'];
        $mail->Password   = $this->config['password'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(
            $this->config['mail_from'],
            $this->config['name_from']
        );

        return $mail;
    }

    private function loadConfig(string $file, string $env): array
    {
        if (!file_exists($file)) {
            throw new \RuntimeException("File mail_service.ini mancante");
        }

        $cfg = parse_ini_file($file, true);

        if (!isset($cfg[$env])) {
            throw new \RuntimeException("Ambiente mail '{$env}' non definito");
        }

        return $cfg[$env];
    }
}
function emailAlert(string $message, string $type = 'info'): string {
    $colors = [
        'primary' => ['border' => '#06c', 'text' => '#06c'],
        'info'    => ['border' => '#5d7083', 'text' => '#5d7083'],
        'success' => ['border' => '#008055', 'text' => '#008055'],
        'error'   => ['border' => '#cc334c', 'text' => '#cc334c'],
        'warning' => ['border' => '#995c00', 'text' => '#995c00'],
    ];

    $c = $colors[$type] ?? $colors['info'];

    return '<div style="
        padding: 12px 15px;
        border-radius: 4px;
        background-color: #ffffff;
        border-left: 4px solid '.$c['border'].';
        color: '.$c['text'].';
        font-family: \'Source Sans Pro\', sans-serif;
        margin: 15px 0;
        font-size: 14px;
        border-top: 1px solid rgb(126, 126, 126);
        border-bottom: 1px solid rgb(126, 126, 126);
        border-right: 1px solid rgb(126, 126, 126);
    ">
        '.$message.'
    </div>';
}
function buildHtmlBody(array $bodyData, string $application = '', ?string $alertMessage = null, string $alertType = 'info', string $logoCid = 'logo_comune', string $istName = ''): string {
    $htmlBody = <<<HTML
    <!DOCTYPE html>
    <html lang="it">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin:0; padding:0; font-family: "Source Sans Pro", sans-serif; background-color: #f6f6f6; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 8px; }
        .header { text-align: center; padding: 20px; }
        .header img { max-width: 120px; height: auto; margin: 0 auto; display: block; }
        .header h1 { margin: 0; font-size: 20px; color: #3a484d; }
        .body-row { padding: 10px 0; color: #3a484d; font-size: 14px; }
        .body-row b { display:inline-block; width: 150px; }
        .footer { margin-top: 30px; font-size: 12px; color: #3a484d; line-height: 1.4; background-color: #d9d9d9; padding: 10px;}
        @media (max-width: 480px) {
            .container { padding: 10px; }
            .body-row b { width: 100%; display: block; margin-bottom: 5px; }
        }
    </style>
    </head>
    <body>
    <div class="container">
        <div class="header">
            <img src="cid:{$logoCid}" alt="Logo {$istName}">
            <h1>{$istName}</h1>
            <span>Servizio {$application}</span>
        </div>
HTML;

    // alert dinamico
    if ($alertMessage) {
        $htmlBody .= emailAlert($alertMessage, $alertType);
    }

    // corpo dinamico
    if (!empty($bodyData) && is_array($bodyData)) {
        foreach ($bodyData as $key => $value) {
            $val = $value ?? '';
            $htmlBody .= "<div class='body-row'><b>".htmlspecialchars($key)."</b> ".htmlspecialchars($val)."</div>";
        }
    }

    // footer
    $htmlBody .= <<<HTML
        <div class="footer">
            Ricevi questo messaggio dal gestionale Rekla del <b>{$istName}</b>.<br>
            Non rispondere a questa mail perchè l'indirizzo non è presidiato.
        </div>
    </div>
    </body>
    </html>
HTML;

    return $htmlBody;
}
