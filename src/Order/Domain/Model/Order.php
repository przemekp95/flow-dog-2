<?php

declare(strict_types=1);

namespace App\Order\Domain\Model;

use App\Order\Domain\Exception\InvalidCustomerId;
use App\Order\Domain\Exception\InvalidItems;

final readonly class Order
{
    /**
     * @param list<OrderLine> $items
     */
    private function __construct(
        public string $id,
        public int $customerId,
        public array $items,
        public int $total,
        public \DateTimeImmutable $createdAt,
        public ?string $couponCode,
    ) {
    }

    /**
     * @param list<OrderLine> $lines
     */
    public static function place(
        string $id,
        int $customerId,
        array $lines,
        \DateTimeImmutable $createdAt,
        ?string $couponCode = null,
    ): self {
        if ($customerId <= 0) {
            throw new InvalidCustomerId();
        }

        if ([] === $lines) {
            throw new InvalidItems();
        }

        self::assertLinesContainUniqueProducts($lines);

        $subtotal = self::calculateSubtotal($lines);
        $total = CouponPolicy::apply($couponCode, $subtotal);

        return new self(
            id: $id,
            customerId: $customerId,
            items: $lines,
            total: max(0, $total),
            createdAt: $createdAt,
            couponCode: $couponCode,
        );
    }

    /**
     * @param list<OrderLine> $lines
     */
    private static function calculateSubtotal(array $lines): int
    {
        return array_reduce(
            $lines,
            static fn (int $carry, OrderLine $line): int => $carry + $line->lineTotal,
            0,
        );
    }

    /**
     * @param list<OrderLine> $lines
     */
    private static function assertLinesContainUniqueProducts(array $lines): void
    {
        $productIds = array_map(
            static fn (OrderLine $line): int => $line->productId,
            $lines,
        );

        if (\count($productIds) === \count(array_unique($productIds))) {
            return;
        }

        throw new InvalidItems('Each product can appear only once in items.');
    }
}
