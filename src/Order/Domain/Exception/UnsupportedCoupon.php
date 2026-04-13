<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainError;

final class UnsupportedCoupon extends \DomainException implements DomainError
{
    public function __construct(?string $couponCode)
    {
        parent::__construct(sprintf('Coupon code "%s" is not supported.', (string) $couponCode));
    }

    public function errorCode(): string
    {
        return 'invalid_coupon';
    }
}
