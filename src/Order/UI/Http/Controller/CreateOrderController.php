<?php

declare(strict_types=1);

namespace App\Order\UI\Http\Controller;

use App\Order\Application\CreateOrder\CreateOrderHandler;
use App\Order\UI\Http\Request\CreateOrderRequestData;
use App\Order\UI\Http\Request\CreateOrderRequestMapper;
use App\Order\UI\Http\Response\CreateOrderResponseData;
use App\Shared\UI\Http\ApiErrorData;
use App\Shared\UI\Http\JsonResponseFactory;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/orders', name: 'app_orders_create', methods: ['POST'])]
final readonly class CreateOrderController
{
    public function __construct(
        private CreateOrderRequestMapper $requestMapper,
        private CreateOrderHandler $handler,
        private JsonResponseFactory $jsonResponseFactory,
    ) {
    }

    #[OA\Post(
        summary: 'Create an order',
        description: 'Creates an order from the provided customer, items and optional coupon.',
        tags: ['Orders'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Order created',
                content: new OA\JsonContent(ref: new Model(type: CreateOrderResponseData::class)),
            ),
            new OA\Response(
                response: 400,
                description: 'Malformed JSON or invalid request payload.',
                content: new OA\JsonContent(
                    ref: new Model(type: ApiErrorData::class),
                    examples: [
                        new OA\Examples(
                            example: 'malformed_json',
                            summary: 'Malformed JSON',
                            value: [
                                'code' => 'malformed_json',
                                'message' => 'Request body must contain valid JSON.',
                            ],
                        ),
                        new OA\Examples(
                            example: 'invalid_request_payload',
                            summary: 'Unexpected root property',
                            value: [
                                'code' => 'invalid_request_payload',
                                'message' => 'Request payload contains invalid "unexpected" property.',
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: 'Product not found',
                content: new OA\JsonContent(ref: new Model(type: ApiErrorData::class)),
            ),
            new OA\Response(
                response: 409,
                description: 'Business conflict',
                content: new OA\JsonContent(ref: new Model(type: ApiErrorData::class)),
            ),
            new OA\Response(
                response: 415,
                description: 'Unsupported media type.',
                content: new OA\JsonContent(
                    ref: new Model(type: ApiErrorData::class),
                    examples: [
                        new OA\Examples(
                            example: 'unsupported_media_type',
                            summary: 'Non-JSON Content-Type',
                            value: [
                                'code' => 'unsupported_media_type',
                                'message' => 'Unsupported format.',
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: new Model(type: ApiErrorData::class)),
            ),
            new OA\Response(
                response: 500,
                description: 'Internal server error',
                content: new OA\JsonContent(ref: new Model(type: ApiErrorData::class)),
            ),
        ],
    )]
    public function __invoke(
        #[MapRequestPayload(
            acceptFormat: 'json',
            serializationContext: [
                AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
            ],
            validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
        )]
        CreateOrderRequestData $requestData,
    ): JsonResponse {
        $command = $this->requestMapper->map($requestData);
        $result = ($this->handler)($command);

        return $this->jsonResponseFactory->create(
            CreateOrderResponseData::fromResult($result)->toArray(),
            Response::HTTP_CREATED,
        );
    }
}
