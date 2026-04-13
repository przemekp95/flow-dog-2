<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

interface DomainError
{
    public function errorCode(): string;
}
