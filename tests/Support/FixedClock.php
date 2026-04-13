<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Order\Application\Port\Clock;

final readonly class FixedClock implements Clock
{
    public function __construct(
        private \DateTimeImmutable $now,
    ) {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
