<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainError;

final class StockExceeded extends \DomainException implements DomainError
{
    public function __construct(int $productId, int $availableStock)
    {
        parent::__construct(sprintf('Product %d has only %d items left in stock.', $productId, $availableStock));
    }

    public function errorCode(): string
    {
        return 'insufficient_stock';
    }
}
