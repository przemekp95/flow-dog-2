<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Catalog;

use App\Order\Application\Port\ProductCatalog;
use App\Order\Domain\Exception\ProductNotFound;
use App\Order\Domain\Model\CatalogProduct;

final class InMemoryProductCatalog implements ProductCatalog
{
    /**
     * @var array<int, array{id:int, name:string, price:int, stock:int, active:bool}>
     */
    private array $products = [
        10 => ['id' => 10, 'name' => 'Keyboard', 'price' => 120, 'stock' => 5, 'active' => true],
        15 => ['id' => 15, 'name' => 'Mouse', 'price' => 80, 'stock' => 0, 'active' => true],
        20 => ['id' => 20, 'name' => 'Monitor', 'price' => 900, 'stock' => 2, 'active' => false],
    ];

    public function getById(int $productId): CatalogProduct
    {
        $product = $this->products[$productId] ?? null;

        if (null === $product) {
            throw new ProductNotFound($productId);
        }

        return new CatalogProduct(
            id: $product['id'],
            name: $product['name'],
            price: $product['price'],
            stock: $product['stock'],
            active: $product['active'],
        );
    }
}
