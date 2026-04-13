<?php

declare(strict_types=1);

namespace App\Order\Domain\Model;

use App\Order\Domain\Exception\ProductInactive;
use App\Order\Domain\Exception\StockExceeded;

final readonly class CatalogProduct
{
    public function __construct(
        public int $id,
        public string $name,
        public int $price,
        public int $stock,
        public bool $active,
    ) {
    }

    public function assertCanBeOrdered(int $quantity): void
    {
        if (!$this->active) {
            throw new ProductInactive($this->id);
        }

        if ($quantity > $this->stock) {
            throw new StockExceeded($this->id, $this->stock);
        }
    }
}
