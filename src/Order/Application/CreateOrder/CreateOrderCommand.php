<?php

declare(strict_types=1);

namespace App\Order\Application\CreateOrder;

final readonly class CreateOrderCommand
{
    /**
     * @param list<CreateOrderItemCommand> $items
     */
    public function __construct(
        public int $customerId,
        public array $items,
        public ?string $couponCode,
    ) {
    }
}
