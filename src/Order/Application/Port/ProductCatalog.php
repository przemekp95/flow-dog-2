<?php

declare(strict_types=1);

namespace App\Order\Application\Port;

use App\Order\Domain\Model\CatalogProduct;

interface ProductCatalog
{
    public function getById(int $productId): CatalogProduct;
}
