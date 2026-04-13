<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Domain;

use App\Order\Domain\Exception\InvalidItems;
use App\Order\Domain\Model\CatalogProduct;
use App\Order\Domain\Model\Order;
use App\Order\Domain\Model\OrderLine;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testItCalculatesTotalInsideTheDomainWithoutCoupon(): void
    {
        $order = Order::place(
            id: '0196254c-8ef5-7f62-9c7e-9a45c7392a18',
            customerId: 123,
            lines: [
                OrderLine::fromCatalogProduct(new CatalogProduct(10, 'Keyboard', 120, 5, true), 2),
            ],
            createdAt: new \DateTimeImmutable('2026-04-11T15:00:00+00:00'),
        );

        self::assertSame(240, $order->total);
    }

    public function testItCalculatesDiscountedTotalInsideTheDomain(): void
    {
        $order = Order::place(
            id: '0196254c-8ef5-7f62-9c7e-9a45c7392a18',
            customerId: 123,
            lines: [
                OrderLine::fromCatalogProduct(new CatalogProduct(10, 'Keyboard', 120, 5, true), 2),
            ],
            createdAt: new \DateTimeImmutable('2026-04-11T15:00:00+00:00'),
            couponCode: 'PROMO10',
        );

        self::assertSame(216, $order->total);
    }

    public function testItRejectsDuplicateProductsInsideTheDomain(): void
    {
        try {
            Order::place(
                id: '0196254c-8ef5-7f62-9c7e-9a45c7392a18',
                customerId: 123,
                lines: [
                    OrderLine::fromCatalogProduct(new CatalogProduct(10, 'Keyboard', 120, 5, true), 1),
                    OrderLine::fromCatalogProduct(new CatalogProduct(10, 'Keyboard', 120, 5, true), 2),
                ],
                createdAt: new \DateTimeImmutable('2026-04-11T15:00:00+00:00'),
            );
            self::fail('Expected InvalidItems to be thrown for duplicate product ids.');
        } catch (InvalidItems $exception) {
            self::assertSame('invalid_items', $exception->errorCode());
            self::assertSame('Each product can appear only once in items.', $exception->getMessage());
        }
    }
}
