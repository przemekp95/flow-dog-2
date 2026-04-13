<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ApiErrorData')]
final readonly class ApiErrorData
{
    public function __construct(
        #[OA\Property(example: 'invalid_quantity')]
        public string $code,
        #[OA\Property(example: 'Quantity must be greater than 0.')]
        public string $message,
    ) {
    }

    /**
     * @return array{code:string, message:string}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
        ];
    }
}
