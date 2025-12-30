<?php
declare(strict_types=1);

/* PHPMailer */
require_once __DIR__ . '/Classes/Services/SMTP/PHPMailer.php';
require_once __DIR__ . '/Classes/Services/SMTP/SMTP.php';
require_once __DIR__ . '/Classes/Services/SMTP/Exception.php';

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/';

    $file = $baseDir . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
