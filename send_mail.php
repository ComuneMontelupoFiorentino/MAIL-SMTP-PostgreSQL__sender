#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Classes\Services\Logger;
use Classes\Services\MailService;
use Classes\Services\MailQueueService;
use Classes\Services\PostgresConnection;
use Classes\Tasks\SendMailTask;
use Classes\Tasks\Dto\MailMessage;

/**
 * ----------------------------
 * CLI OPTIONS (future)
 * ----------------------------
 */
$longOpts = [
    // 'subject:',
    // 'dry-run',
];
$options = getopt("", $longOpts);

/**
 * ----------------------------
 * CLI BASE PARSING
 * ----------------------------
 */
$envFlag = $argv[1] ?? null;
$command = $argv[2] ?? null;

if (!$envFlag || !$command) {
    echo "Uso:\n";
    echo "  php send_mail.php -test|-prod -p\n";
    echo "  php send_mail.php -test|-prod -q\n";
    exit(1);
}

/**
 * ----------------------------
 * ENVIRONMENT
 * ----------------------------
 */
switch ($envFlag) {
    case '-test':
        $pgEnv  = 'pg_test';
        $appEnv = 'test';
        break;

    case '-prod':
        $pgEnv  = 'pg_prod';
        $appEnv = 'prod';
        break;

    default:
        echo "Ambiente non valido (-test|-prod)\n";
        exit(1);
}

echo "ENV: {$appEnv}\n";

/**
 * ----------------------------
 * SERVICES
 * ----------------------------
 */
$logger = new Logger(__DIR__ . '/logs');
$logger->setTask('send_mail');

$mailService = new MailService(
    $appEnv,
    __DIR__ . '/config/mail_service.ini',
    $logger
);

$sendMailTask = new SendMailTask($mailService, $logger);

/**
 * ----------------------------
 * FUNZIONI
 * ----------------------------
 */

/**
 * Invio mail singola da file ini
 */
function sendSingleMail(SendMailTask $task, Logger $logger): void
{
    $file = __DIR__ . '/config/mail_params.ini';

    if (!file_exists($file)) {
        throw new RuntimeException("File mail_params.ini non trovato");
    }

    $cfg = parse_ini_file($file);

    $message = new \Classes\Tasks\Dto\MailMessage(
        to: array_map('trim', explode(',', $cfg['to'] ?? '')),
        cc: !empty($cfg['cc']) ? array_map('trim', explode(',', $cfg['cc'])) : [],
        subject: $cfg['subject'] ?? '',
        bodyHtml: $cfg['bodyHTML'] ?? '',
        attachments: !empty($cfg['attachments'])
            ? array_map('trim', explode(',', $cfg['attachments']))
            : []
    );

    // usa la variabile passata come parametro
    $task->send($message);
    $logger->info("Invio mail singola completato");
}

/**
 * Invio mail da coda DB
 */
function sendQueueMail(SendMailTask $task, Logger $logger, string $pgEnv): void
{
    $pgServiceFile = __DIR__ . '/config/pg_service.conf';

    $db = new \Classes\Services\PostgresConnection($pgEnv, $pgServiceFile, $logger);

    $pdo = $db->getPdo();
    $tableQueue = $db->getTableQueue();

    $queue = new \Classes\Services\MailQueueService($pdo, $tableQueue);

    foreach ($queue->getPending() as $messageData) {
        $success = $task->send($messageData);

        // Aggiornamento stato nel DB
        $status = $success ? 'sended' : 'error';
        $stmt = $pdo->prepare("UPDATE {$tableQueue} SET status = :status, sent_at = NOW() WHERE id = :id");
        $stmt->execute([
            ':status' => $status,
            ':id'     => $messageData->id  // supponendo che MailMessage abbia la proprietà id
        ]);

        $logger->info("Email ID {$messageData->id} inviata: status={$status}");
    }

    $logger->info("Invio mail da coda completato");
}

/**
 * ----------------------------
 * TASK DISPATCH
 * ----------------------------
 */
try {
    switch ($command) {

        // SEND MAIL FROM PARAMS
        case '-p':
            sendSingleMail($sendMailTask, $logger);
            break;

        // SEND MAIL FROM QUEUE
        case '-q':
            sendQueueMail($sendMailTask, $logger, $pgEnv);
            break;

        default:
            throw new RuntimeException("Comando non valido: {$command}");
    }

} catch (Throwable $e) {
    $logger->error($e->getMessage());
    echo "ERRORE: {$e->getMessage()}\n";
    exit(1);
}
