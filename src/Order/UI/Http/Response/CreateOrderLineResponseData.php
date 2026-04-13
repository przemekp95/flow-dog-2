<?php

declare(strict_types=1);

namespace App\Order\UI\Http\Response;

use App\Order\Application\CreateOrder\CreateOrderResultLine;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CreateOrderLineResponseData')]
final readonly class CreateOrderLineResponseData
{
    public function __construct(
        #[OA\Property(example: 10)]
        public int $productId,
        #[OA\Property(example: 'Keyboard')]
        public string $name,
        #[OA\Property(example: 2)]
        public int $quantity,
        #[OA\Property(example: 120)]
        public int $price,
        #[OA\Property(example: 240)]
        public int $lineTotal,
    ) {
    }

    public static function fromResultLine(CreateOrderResultLine $line): self
    {
        return new self(
            productId: $line->productId,
            name: $line->name,
            quantity: $line->quantity,
            price: $line->price,
            lineTotal: $line->lineTotal,
        );
    }

    /**
     * @return array{productId:int, name:string, quantity:int, price:int, lineTotal:int}
     */
    public function toArray(): array
    {
        return [
            'productId' => $this->productId,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'lineTotal' => $this->lineTotal,
        ];
    }
}
