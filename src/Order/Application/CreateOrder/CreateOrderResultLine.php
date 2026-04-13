<?php

declare(strict_types=1);

namespace App\Order\Application\CreateOrder;

use App\Order\Domain\Model\OrderLine;

final readonly class CreateOrderResultLine
{
    public function __construct(
        public int $productId,
        public string $name,
        public int $quantity,
        public int $price,
        public int $lineTotal,
    ) {
    }

    public static function fromOrderLine(OrderLine $line): self
    {
        return new self(
            productId: $line->productId,
            name: $line->name,
            quantity: $line->quantity,
            price: $line->price,
            lineTotal: $line->lineTotal,
        );
    }
}
