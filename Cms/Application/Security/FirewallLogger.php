<?php

declare(strict_types=1);

namespace Application\Security;

use Codefy\Framework\Security\Firewall\ThreatLogger;
use Codefy\Framework\Security\Firewall\ThreatMatch;
use Psr\Http\Message\ServerRequestInterface;

use function Codefy\Framework\Helpers\logger;

final class FirewallLogger implements ThreatLogger
{
    public function log(ServerRequestInterface $request, ThreatMatch $match): void
    {
        // Do not log bodies, query strings, credentials, or the matched input value.
        logger('warning', 'Firewall rule matched.', [
            'type' => $match->type,
            'severity' => $match->severity,
            'source' => $match->source,
            'method' => $request->getMethod(),
            'excluded' => $match->excluded,
        ]);
    }
}
