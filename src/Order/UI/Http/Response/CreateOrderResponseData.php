<?php

declare(strict_types=1);

namespace App\Order\UI\Http\Response;

use App\Order\Application\CreateOrder\CreateOrderResult;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CreateOrderResponseData')]
final readonly class CreateOrderResponseData
{
    /**
     * @param list<CreateOrderLineResponseData> $items
     */
    public function __construct(
        #[OA\Property(example: '0196254c-8ef5-7f62-9c7e-9a45c7392a18')]
        public string $id,
        #[OA\Property(example: 123)]
        public int $customerId,
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: CreateOrderLineResponseData::class)))]
        public array $items,
        #[OA\Property(example: 216)]
        public int $total,
        #[OA\Property(example: '2026-04-11T15:00:00+00:00')]
        public string $createdAt,
        #[OA\Property(example: 'PROMO10', nullable: true)]
        public ?string $couponCode,
    ) {
    }

    public static function fromResult(CreateOrderResult $result): self
    {
        return new self(
            id: $result->id,
            customerId: $result->customerId,
            items: array_map(
                static fn ($line): CreateOrderLineResponseData => CreateOrderLineResponseData::fromResultLine($line),
                $result->items,
            ),
            total: $result->total,
            createdAt: $result->createdAt,
            couponCode: $result->couponCode,
        );
    }

    /**
     * @return array{id:string, customerId:int, items:list<array{productId:int, name:string, quantity:int, price:int, lineTotal:int}>, total:int, createdAt:string, couponCode?:string}
     */
    public function toArray(): array
    {
        $payload = [
            'id' => $this->id,
            'customerId' => $this->customerId,
            'items' => array_map(
                static fn (CreateOrderLineResponseData $item): array => $item->toArray(),
                $this->items,
            ),
            'total' => $this->total,
            'createdAt' => $this->createdAt,
        ];

        if (null !== $this->couponCode) {
            $payload['couponCode'] = $this->couponCode;
        }

        return $payload;
    }
}
