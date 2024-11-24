<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use Qubus\Http\Session\SessionEntity;

final class CmsUserSession implements SessionEntity
{
    public ?string $token = null;

    public function withToken(?string $token = null): self
    {
        $this->token = $token;
        return $this;
    }

    public function clear(): void
    {
        if (!empty($this->token)) {
            unset($this->token);
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->token);
    }
}
