<?php
declare(strict_types=1);

namespace Classes\Services;

class Logger
{
    private string $task = 'app';
    private int $taskId = 0;

    public function __construct(
        private string $baseLogDir
    ) {}

    public function setTask(string $task): void
    {
        $this->task = $task;
    }

    public function setTaskId(int $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function info(string $message): void
    {
        $this->writeTaskLog('INFO', $message);
    }

    public function error(string $message): void
    {
        $this->writeTaskLog('ERROR', $message);
        $this->writeMonthlyErrorLog($message);
    }

    /* ================= PRIVATE ================= */

    private function writeTaskLog(string $level, string $message): void
    {
        $file = $this->getDailyTaskLogFile();
        $this->write($file, $level, $message);
    }

    private function writeMonthlyErrorLog(string $message): void
    {
        $file = $this->getMonthlyErrorLogFile();
        $this->write($file, 'ERROR', "[{$this->task}] {$message}");
    }

    private function write(string $file, string $level, string $message): void
    {
        $this->prepareDir(dirname($file));

        file_put_contents(
            $file,
            sprintf("[%s][%s] %s\n", date('Y-m-d H:i:s'), $level, $message),
            FILE_APPEND | LOCK_EX
        );
    }

    private function getDailyTaskLogFile(): string
    {
        return sprintf(
            '%s/%s/%s/%s/%s.log',
            rtrim($this->baseLogDir, '/'),
            date('Y'),
            date('m'),
            date('d'),
            $this->taskId ?: $this->task
        );
    }

    private function getMonthlyErrorLogFile(): string
    {
        return sprintf(
            '%s/%s/%s/errors.log',
            rtrim($this->baseLogDir, '/'),
            date('Y'),
            date('m')
        );
    }

    private function prepareDir(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
}
