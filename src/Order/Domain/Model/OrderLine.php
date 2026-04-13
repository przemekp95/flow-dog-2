<?php

declare(strict_types=1);

namespace App\Order\Domain\Model;

use App\Order\Domain\Exception\InvalidQuantity;

final readonly class OrderLine
{
    private function __construct(
        public int $productId,
        public string $name,
        public int $quantity,
        public int $price,
        public int $lineTotal,
    ) {
    }

    public static function fromCatalogProduct(CatalogProduct $product, int $quantity): self
    {
        if ($quantity <= 0) {
            throw new InvalidQuantity();
        }

        $product->assertCanBeOrdered($quantity);

        return new self(
            productId: $product->id,
            name: $product->name,
            quantity: $quantity,
            price: $product->price,
            lineTotal: $product->price * $quantity,
        );
    }
}
