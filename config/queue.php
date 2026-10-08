<?php

declare(strict_types=1);

return [
    // Explicit allowlist of SerializableJob classes restored by queue:list and queue:run.
    // Use one job class per queue name; migrate legacy jobs before starting 4.x workers.
    'jobs' => [],
];
