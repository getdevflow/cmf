<?php

declare(strict_types=1);

namespace Application\Provider;

use Codefy\Framework\Security\Firewall\NullThreatLogger;
use Codefy\Framework\Security\Firewall\ThreatLogger;
use Codefy\Framework\Support\CodefyServiceProvider;

class FirewallServiceProvider extends CodefyServiceProvider
{
    public function register(): void
    {
        $this->codefy->alias(ThreatLogger::class, NullThreatLogger::class);
    }
}
