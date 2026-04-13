<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainError;

final class InvalidCustomerId extends \DomainException implements DomainError
{
    public function __construct()
    {
        parent::__construct('Customer ID must be a positive integer.');
    }

    public function errorCode(): string
    {
        return 'invalid_customer_id';
    }
}
