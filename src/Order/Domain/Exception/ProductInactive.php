<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainError;

final class ProductInactive extends \DomainException implements DomainError
{
    public function __construct(int $productId)
    {
        parent::__construct(sprintf('Product %d is inactive.', $productId));
    }

    public function errorCode(): string
    {
        return 'inactive_product';
    }
}
