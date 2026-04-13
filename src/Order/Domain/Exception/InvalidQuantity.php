<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainError;

final class InvalidQuantity extends \DomainException implements DomainError
{
    public function __construct()
    {
        parent::__construct('Quantity must be greater than 0.');
    }

    public function errorCode(): string
    {
        return 'invalid_quantity';
    }
}
