<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Uuid;

use App\Order\Application\Port\IdGenerator;
use Symfony\Component\Uid\Uuid;

final class SymfonyUuidGenerator implements IdGenerator
{
    public function generate(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}
