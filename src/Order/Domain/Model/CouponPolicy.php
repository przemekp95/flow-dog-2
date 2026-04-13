<?php

declare(strict_types=1);

namespace App\Order\Domain\Model;

use App\Order\Domain\Exception\UnsupportedCoupon;

final class CouponPolicy
{
    public static function apply(?string $couponCode, int $subtotal): int
    {
        if (null === $couponCode || '' === $couponCode) {
            return $subtotal;
        }

        return match ($couponCode) {
            'PROMO10' => (int) round($subtotal * 0.9),
            'MINUS50' => $subtotal >= 300 ? $subtotal - 50 : $subtotal,
            default => throw new UnsupportedCoupon($couponCode),
        };
    }
}
