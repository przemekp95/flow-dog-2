<?php

declare(strict_types=1);

namespace App\Tests\Integration\Order\UI\Http\Request;

use App\Order\UI\Http\Request\CreateOrderRequestData;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateOrderRequestDataFlowTest extends KernelTestCase
{
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $serializer = $container->get(SerializerInterface::class);
        $validator = $container->get(ValidatorInterface::class);

        self::assertInstanceOf(SerializerInterface::class, $serializer);
        self::assertInstanceOf(ValidatorInterface::class, $validator);

        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    public function testSerializerDeserializesValidPayloadIntoRequestDto(): void
    {
        $request = $this->deserialize(
            <<<'JSON'
            {
                "customerId": 123,
                "items": [
                    {
                        "productId": 10,
                        "quantity": 2
                    }
                ],
                "couponCode": "PROMO10"
            }
            JSON,
        );

        self::assertSame(123, $request->customerId);
        self::assertSame(
            [
                [
                    'productId' => 10,
                    'quantity' => 2,
                ],
            ],
            $request->items,
        );
        self::assertSame('PROMO10', $request->couponCode);
    }

    public function testSerializerThrowsForMalformedJson(): void
    {
        $this->expectException(NotEncodableValueException::class);

        $this->deserialize('{"customerId": 123,');
    }

    public function testValidatorReturnsMissingFieldViolationForMissingCustomerId(): void
    {
        $request = $this->deserialize(
            <<<'JSON'
            {
                "items": [
                    {
                        "productId": 10,
                        "quantity": 2
                    }
                ]
            }
            JSON,
        );

        $violation = $this->firstViolation($request);

        self::assertSame('customerId', $violation->getPropertyPath());
        self::assertSame('Field "customerId" is required.', $violation->getMessage());
        self::assertSame('missing_field', $this->errorCode($violation));
    }

    public function testValidatorReturnsInvalidQuantityViolationForNestedItem(): void
    {
        $request = $this->deserialize(
            <<<'JSON'
            {
                "customerId": 123,
                "items": [
                    {
                        "productId": 10,
                        "quantity": 0
                    }
                ]
            }
            JSON,
        );

        $violation = $this->firstViolation($request);

        self::assertStringContainsString('quantity', $violation->getPropertyPath());
        self::assertSame('Quantity must be greater than 0.', $violation->getMessage());
        self::assertSame('invalid_quantity', $this->errorCode($violation));
    }

    public function testValidatorReturnsInvalidItemsViolationForUnexpectedFieldInsideItem(): void
    {
        $request = $this->deserialize(
            <<<'JSON'
            {
                "customerId": 123,
                "items": [
                    {
                        "productId": 10,
                        "quantity": 2,
                        "unexpected": "value"
                    }
                ]
            }
            JSON,
        );

        $violation = $this->firstViolation($request);

        self::assertStringContainsString('items', $violation->getPropertyPath());
        self::assertSame('Unexpected field "unexpected" is not allowed.', $violation->getMessage());
        self::assertSame(Collection::NO_SUCH_FIELD_ERROR, $violation->getCode());
    }

    private function deserialize(string $json): CreateOrderRequestData
    {
        return $this->serializer->deserialize($json, CreateOrderRequestData::class, 'json');
    }

    private function firstViolation(CreateOrderRequestData $request): ConstraintViolationInterface
    {
        $violations = $this->validator->validate($request);
        $violation = $violations[0] ?? null;

        self::assertInstanceOf(ConstraintViolationInterface::class, $violation);

        return $violation;
    }

    private function errorCode(ConstraintViolationInterface $violation): ?string
    {
        $payload = $violation->getConstraint()?->payload;

        if (!\is_array($payload)) {
            return null;
        }

        $errorCode = $payload['error_code'] ?? null;

        return \is_string($errorCode) ? $errorCode : null;
    }
}
