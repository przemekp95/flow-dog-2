<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Domain;

use App\Order\Domain\Exception\UnsupportedCoupon;
use App\Order\Domain\Model\CouponPolicy;
use PHPUnit\Framework\TestCase;

final class CouponPolicyTest extends TestCase
{
    public function testItAppliesPromo10Coupon(): void
    {
        self::assertSame(216, CouponPolicy::apply('PROMO10', 240));
    }

    public function testItAppliesMinus50WhenThresholdIsMet(): void
    {
        self::assertSame(310, CouponPolicy::apply('MINUS50', 360));
    }

    public function testItDoesNotApplyMinus50BelowThreshold(): void
    {
        self::assertSame(240, CouponPolicy::apply('MINUS50', 240));
    }

    public function testItRejectsUnsupportedCouponCodes(): void
    {
        $this->expectException(UnsupportedCoupon::class);

        CouponPolicy::apply('FREEMONEY', 240);
    }
}
