<?php

declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\DomainError;

final class InvalidItems extends \DomainException implements DomainError
{
    public function __construct(string $message = 'Items must be a non-empty array.')
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'invalid_items';
    }
}
