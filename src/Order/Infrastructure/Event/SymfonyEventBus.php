<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Event;

use App\Order\Application\Port\EventBus;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class SymfonyEventBus implements EventBus
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function publish(object $event): void
    {
        $this->eventDispatcher->dispatch($event);
    }
}
