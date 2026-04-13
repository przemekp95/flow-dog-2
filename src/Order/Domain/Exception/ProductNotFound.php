<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainError;

final class ProductNotFound extends \DomainException implements DomainError
{
    public function __construct(int $productId)
    {
        parent::__construct(sprintf('Product %d was not found.', $productId));
    }

    public function errorCode(): string
    {
        return 'product_not_found';
    }
}
