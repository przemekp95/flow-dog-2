<?php

declare(strict_types=1);

namespace App\Order\Application\CreateOrder;

final readonly class CreateOrderItemCommand
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {
    }
}
