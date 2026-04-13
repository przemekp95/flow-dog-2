<?php

declare(strict_types=1);

namespace App\Order\Application\Port;

interface EventBus
{
    public function publish(object $event): void;
}
