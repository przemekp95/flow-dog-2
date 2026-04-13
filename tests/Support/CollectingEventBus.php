<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Order\Application\Port\EventBus;

final class CollectingEventBus implements EventBus
{
    /**
     * @var list<object>
     */
    public array $events = [];

    public function publish(object $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return list<object>
     */
    public function events(): array
    {
        return $this->events;
    }
}
