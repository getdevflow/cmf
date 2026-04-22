<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Codefy\Framework\Contracts\Http\Kernel;

use function Codefy\Framework\Helpers\get_fresh_bootstrap;

$app = get_fresh_bootstrap();

$kernel = $app->make(Kernel::class);
$kernel->boot();
