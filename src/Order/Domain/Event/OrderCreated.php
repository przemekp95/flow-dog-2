<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Order\Domain\Model\Order;

final readonly class OrderCreated
{
    public function __construct(
        public string $orderId,
        public int $customerId,
        public int $total,
        public string $createdAt,
    ) {
    }

    public static function fromOrder(Order $order): self
    {
        return new self(
            orderId: $order->id,
            customerId: $order->customerId,
            total: $order->total,
            createdAt: $order->createdAt->format(\DATE_ATOM),
        );
    }
}
