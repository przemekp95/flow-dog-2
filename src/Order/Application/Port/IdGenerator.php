<?php

declare(strict_types=1);

namespace App\Order\Application\Port;

interface IdGenerator
{
    public function generate(): string;
}
