<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Codefy\Framework\Http\Kernel;
use Dotenv\Dotenv;

use function Codefy\Framework\Helpers\base_path;
use function Codefy\Framework\Helpers\get_fresh_bootstrap;

$dotenv = Dotenv::createImmutable(paths: base_path());
$dotenv->safeLoad();

$app = get_fresh_bootstrap();

$kernel = $app->make(Kernel::class);

$kernel->boot();
