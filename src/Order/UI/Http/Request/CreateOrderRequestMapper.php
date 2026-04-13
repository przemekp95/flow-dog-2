<?php

declare(strict_types=1);

namespace App\Order\UI\Http\Request;

use App\Order\Application\CreateOrder\CreateOrderCommand;
use App\Order\Application\CreateOrder\CreateOrderItemCommand;

final class CreateOrderRequestMapper
{
    public function map(CreateOrderRequestData $payload): CreateOrderCommand
    {
        /** @var int $customerId */
        $customerId = $payload->customerId;
        /** @var list<array{productId: int, quantity: int}> $requestItems */
        $requestItems = $payload->items;
        /** @var ?string $couponCode */
        $couponCode = $payload->couponCode;

        $items = array_map(
            static fn (array $item): CreateOrderItemCommand => new CreateOrderItemCommand(
                productId: $item['productId'],
                quantity: $item['quantity'],
            ),
            $requestItems,
        );

        return new CreateOrderCommand(
            customerId: $customerId,
            items: $items,
            couponCode: $couponCode,
        );
    }
}
