<?php

declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';

use Codefy\Framework\Contracts\Http\Kernel;

use function Codefy\Framework\Helpers\get_fresh_bootstrap;

$app = get_fresh_bootstrap();

$kernel = $app->make(Kernel::class);
$kernel->boot();
