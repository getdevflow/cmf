<?php

declare(strict_types=1);

use function Codefy\Framework\Helpers\env;
use function Codefy\Framework\Helpers\storage_path;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Symfony Mailer DSN
    |--------------------------------------------------------------------------
    |
    | Any DSN supported by Symfony Mailer and the installed provider bridges.
    | Keep credentials URL-encoded. Examples:
    |
    | smtp://user:password@smtp.example.com:587?require_tls=true
    | postmark+api://KEY@default
    | failover(postmark+api://KEY@default smtp://localhost)
    |
    */
    'dsn' => env(key: 'MAILER_DSN', default: 'smtp://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Named Transports
    |--------------------------------------------------------------------------
    |
    | Select these using withTransport('transactional'), withSmtp(), etc.
    | Provider transports require their corresponding Symfony bridge package.
    |
    */
    'transports' => [
        'smtp' => env(key: 'MAILER_SMTP_DSN', default: 'smtp://localhost'),
        'sendmail' => env(key: 'MAILER_SENDMAIL_DSN', default: 'sendmail://default'),
        'qmail' => env(key: 'MAILER_QMAIL_DSN', default: 'sendmail://default?command=/usr/sbin/qmail-inject'),
        'transactional' => env(key: 'MAILER_TRANSACTIONAL_DSN', default: 'postmark+api://KEY@default'),
    ],

    /* Save to emlfile instead of sending. */
    'debug' => (bool) (env(key: 'MAILER_DEBUG', default: false)),

    /* RFC 822 output path used in debug mode. */
    'emlfile' => env(key: 'MAILER_EML_FILE', default: storage_path('mail/debug.eml')),
];
