<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Domain;

use App\Order\Domain\Exception\InvalidQuantity;
use App\Order\Domain\Exception\ProductInactive;
use App\Order\Domain\Exception\StockExceeded;
use App\Order\Domain\Model\CatalogProduct;
use App\Order\Domain\Model\OrderLine;
use PHPUnit\Framework\TestCase;

final class OrderLineTest extends TestCase
{
    public function testItCreatesALineWithLineTotal(): void
    {
        $line = OrderLine::fromCatalogProduct(
            new CatalogProduct(10, 'Keyboard', 120, 5, true),
            2,
        );

        self::assertSame(240, $line->lineTotal);
    }

    public function testItRejectsQuantityLessThanOne(): void
    {
        $this->expectException(InvalidQuantity::class);

        OrderLine::fromCatalogProduct(new CatalogProduct(10, 'Keyboard', 120, 5, true), 0);
    }

    public function testItRejectsInactiveProducts(): void
    {
        $this->expectException(ProductInactive::class);

        OrderLine::fromCatalogProduct(new CatalogProduct(20, 'Monitor', 900, 2, false), 1);
    }

    public function testItRejectsQuantitiesAboveStock(): void
    {
        $this->expectException(StockExceeded::class);

        OrderLine::fromCatalogProduct(new CatalogProduct(10, 'Keyboard', 120, 5, true), 6);
    }
}
