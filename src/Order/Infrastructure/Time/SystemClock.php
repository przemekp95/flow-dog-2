<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Time;

use App\Order\Application\Port\Clock;

final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
