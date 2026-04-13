<?php

declare(strict_types=1);

namespace App\Order\UI\Http\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    type: 'object',
    required: ['customerId', 'items'],
    additionalProperties: false,
)]
final readonly class CreateOrderRequestData
{
    public function __construct(
        #[OA\Property(type: 'integer', example: 123, minimum: 0, exclusiveMinimum: true)]
        #[Assert\NotNull(
            message: 'Field "customerId" is required.',
            payload: ['error_code' => 'missing_field'],
        )]
        #[Assert\Type(
            type: 'integer',
            message: 'Customer id must be an integer.',
            payload: ['error_code' => 'invalid_customer_id'],
        )]
        #[Assert\Positive(
            message: 'Customer id must be greater than 0.',
            payload: ['error_code' => 'invalid_customer_id'],
        )]
        public mixed $customerId = null,
        #[OA\Property(
            type: 'array',
            minItems: 1,
            description: 'Each productId can appear at most once in items.',
            items: new OA\Items(
                type: 'object',
                required: ['productId', 'quantity'],
                additionalProperties: false,
                properties: [
                    new OA\Property(property: 'productId', type: 'integer', example: 10, minimum: 1),
                    new OA\Property(property: 'quantity', type: 'integer', example: 2, minimum: 1),
                ],
            ),
        )]
        #[Assert\NotNull(
            message: 'Field "items" is required.',
            payload: ['error_code' => 'missing_field'],
        )]
        #[Assert\Type(
            type: 'array',
            message: 'Items must be an array.',
            payload: ['error_code' => 'invalid_items'],
        )]
        #[Assert\Count(
            min: 1,
            minMessage: 'At least one item is required.',
            payload: ['error_code' => 'invalid_items'],
        )]
        #[Assert\All(
            constraints: [
                new Assert\Type(
                    type: 'array',
                    message: 'Each item must be an object.',
                    payload: ['error_code' => 'invalid_items'],
                ),
                new Assert\Collection(
                    fields: [
                        'productId' => [
                            new Assert\NotNull(
                                message: 'Field "productId" is required.',
                                payload: ['error_code' => 'missing_field'],
                            ),
                            new Assert\Type(
                                type: 'integer',
                                message: 'Product id must be an integer.',
                                payload: ['error_code' => 'invalid_product_id'],
                            ),
                            new Assert\Positive(
                                message: 'Product id must be greater than 0.',
                                payload: ['error_code' => 'invalid_product_id'],
                            ),
                        ],
                        'quantity' => [
                            new Assert\NotNull(
                                message: 'Field "quantity" is required.',
                                payload: ['error_code' => 'missing_field'],
                            ),
                            new Assert\Type(
                                type: 'integer',
                                message: 'Quantity must be an integer.',
                                payload: ['error_code' => 'invalid_quantity'],
                            ),
                            new Assert\Positive(
                                message: 'Quantity must be greater than 0.',
                                payload: ['error_code' => 'invalid_quantity'],
                            ),
                        ],
                    ],
                    allowExtraFields: false,
                    allowMissingFields: false,
                    extraFieldsMessage: 'Unexpected field {{ field }} is not allowed.',
                    payload: ['error_code' => 'missing_field'],
                ),
            ],
        )]
        public mixed $items = null,
        #[OA\Property(type: 'string', example: 'PROMO10', nullable: true)]
        #[Assert\Type(
            type: 'string',
            message: 'Coupon code must be a string when provided.',
            payload: ['error_code' => 'invalid_coupon'],
        )]
        public mixed $couponCode = null,
    ) {
    }
}
