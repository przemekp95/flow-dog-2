<?php

declare(strict_types=1);

namespace App\Order\Application\CreateOrder;

use App\Order\Domain\Model\Order;

final readonly class CreateOrderResult
{
    /**
     * @param list<CreateOrderResultLine> $items
     */
    public function __construct(
        public string $id,
        public int $customerId,
        public array $items,
        public int $total,
        public string $createdAt,
        public ?string $couponCode,
    ) {
    }

    public static function fromOrder(Order $order): self
    {
        return new self(
            id: $order->id,
            customerId: $order->customerId,
            items: array_map(
                static fn ($line): CreateOrderResultLine => CreateOrderResultLine::fromOrderLine($line),
                $order->items,
            ),
            total: $order->total,
            createdAt: $order->createdAt->format(\DATE_ATOM),
            couponCode: $order->couponCode,
        );
    }
}
