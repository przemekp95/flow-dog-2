<?php

declare(strict_types=1);

namespace App\Order\Application\Port;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
