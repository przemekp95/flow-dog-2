<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Order\Application\Port\IdGenerator;

final readonly class StaticIdGenerator implements IdGenerator
{
    public function __construct(
        private string $id,
    ) {
    }

    public function generate(): string
    {
        return $this->id;
    }
}
