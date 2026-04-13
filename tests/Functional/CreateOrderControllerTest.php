<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Order\Application\CreateOrder\CreateOrderHandler;
use App\Order\Application\Port\Clock;
use App\Order\Application\Port\EventBus;
use App\Order\Application\Port\IdGenerator;
use App\Order\Application\Port\OrderRepository;
use App\Order\Application\Port\ProductCatalog;
use App\Order\Domain\Model\Order;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateOrderControllerTest extends WebTestCase
{
    public function testItCreatesAnOrderWithoutCoupon(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 2],
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array{total:int, items:list<array{lineTotal:int}>} $payload */
        $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame(240, $payload['total']);
        self::assertSame(240, $payload['items'][0]['lineTotal']);
        self::assertArrayNotHasKey('couponCode', $payload);
    }

    public function testItReturns422ForUnexpectedPropertiesInsideItems(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 2, 'ignoredField' => 'value'],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('invalid_items', $this->decodedResponse($client)['code']);
    }

    public function testItTreatsEmptyCouponCodeAsNoDiscount(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 2],
            ],
            'couponCode' => '',
        ]);

        self::assertResponseStatusCodeSame(201);
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array{couponCode:string, total:int} $payload */
        $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame(240, $payload['total']);
        self::assertSame('', $payload['couponCode']);
    }

    public function testItCreatesAnOrderWithPromo10Coupon(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 2],
            ],
            'couponCode' => 'PROMO10',
        ]);

        self::assertResponseStatusCodeSame(201);
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array{total:int} $payload */
        $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame(216, $payload['total']);
    }

    public function testItCreatesAnOrderWithMinus50CouponWhenThresholdIsMet(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 3],
            ],
            'couponCode' => 'MINUS50',
        ]);

        self::assertResponseStatusCodeSame(201);
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array{total:int} $payload */
        $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame(310, $payload['total']);
    }

    public function testItReturns422WhenCustomerIdIsMissing(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'items' => [
                ['productId' => 10, 'quantity' => 2],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('missing_field', $this->decodedResponse($client)['code']);
    }

    public function testItReturns422WhenItemsAreMissing(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('missing_field', $this->decodedResponse($client)['code']);
    }

    public function testItReturns422WhenItemsAreEmpty(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('invalid_items', $this->decodedResponse($client)['code']);
    }

    public function testItReturns422WhenQuantityIsInvalid(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 0],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('invalid_quantity', $this->decodedResponse($client)['code']);
    }

    public function testItReturns422WhenItemsContainDuplicateProducts(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 1],
                ['productId' => 10, 'quantity' => 2],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('invalid_items', $this->decodedResponse($client)['code']);
    }

    public function testItReturns422ForDuplicateUnknownProductsBeforeCatalogLookup(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 999, 'quantity' => 1],
                ['productId' => 999, 'quantity' => 2],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('invalid_items', $this->decodedResponse($client)['code']);
    }

    public function testItReturns404ForUnknownProducts(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 999, 'quantity' => 1],
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
        self::assertSame('product_not_found', $this->decodedResponse($client)['code']);
    }

    public function testItReturns409ForInactiveProducts(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 20, 'quantity' => 1],
            ],
        ]);

        self::assertResponseStatusCodeSame(409);
        self::assertSame('inactive_product', $this->decodedResponse($client)['code']);
    }

    public function testItReturns409WhenStockIsInsufficient(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 15, 'quantity' => 1],
            ],
        ]);

        self::assertResponseStatusCodeSame(409);
        self::assertSame('insufficient_stock', $this->decodedResponse($client)['code']);
    }

    public function testItReturns422ForUnsupportedCoupons(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 2],
            ],
            'couponCode' => 'FREEMONEY',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('invalid_coupon', $this->decodedResponse($client)['code']);
    }

    public function testItReturns422WhenCouponCodeHasInvalidType(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 2],
            ],
            'couponCode' => ['PROMO10'],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('invalid_coupon', $this->decodedResponse($client)['code']);
    }

    public function testItReturns405AsJsonForRoutesWithUnsupportedMethod(): void
    {
        $client = static::createClient();
        $client->request('GET', '/orders');

        self::assertResponseStatusCodeSame(405);
        self::assertSame(
            [
                'code' => 'method_not_allowed',
                'message' => 'Method not allowed.',
            ],
            $this->decodedResponse($client),
        );
    }

    public function testItReturns404AsJsonForUnknownRoutes(): void
    {
        $client = static::createClient();
        $client->request('GET', '/nie-istnieje');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(
            [
                'code' => 'route_not_found',
                'message' => 'Route not found.',
            ],
            $this->decodedResponse($client),
        );
    }

    public function testItReturns400ForMalformedJson(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/orders',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"customerId": 123,',
        );

        self::assertResponseStatusCodeSame(400);
        self::assertSame('malformed_json', $this->decodedResponse($client)['code']);
    }

    public function testItReturns400ForUnexpectedRootProperty(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 2],
            ],
            'unexpected' => 'value',
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertSame('invalid_request_payload', $this->decodedResponse($client)['code']);
    }

    public function testItReturns415ForUnsupportedMediaType(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/orders',
            server: ['CONTENT_TYPE' => 'text/plain'],
            content: '{"customerId":123,"items":[{"productId":10,"quantity":2}]}',
        );

        self::assertResponseStatusCodeSame(415);
        self::assertSame('unsupported_media_type', $this->decodedResponse($client)['code']);
    }

    public function testItReturnsJson500WhenUnexpectedServerErrorOccurs(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $failingRepository = new class implements OrderRepository {
            public function save(Order $order): void
            {
                throw new \RuntimeException('Disk full.');
            }
        };

        $container->set(OrderRepository::class, $failingRepository);
        $productCatalog = $container->get(ProductCatalog::class);
        $eventBus = $container->get(EventBus::class);
        $clock = $container->get(Clock::class);
        $idGenerator = $container->get(IdGenerator::class);
        self::assertInstanceOf(ProductCatalog::class, $productCatalog);
        self::assertInstanceOf(EventBus::class, $eventBus);
        self::assertInstanceOf(Clock::class, $clock);
        self::assertInstanceOf(IdGenerator::class, $idGenerator);
        $handler = new CreateOrderHandler(
            $productCatalog,
            $failingRepository,
            $eventBus,
            $clock,
            $idGenerator,
        );

        $container->set(CreateOrderHandler::class, $handler);

        $client->jsonRequest('POST', '/orders', [
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 2],
            ],
        ]);

        self::assertResponseStatusCodeSame(500);
        self::assertSame(
            [
                'code' => 'internal_error',
                'message' => 'Internal server error.',
            ],
            $this->decodedResponse($client),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodedResponse(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array<string, mixed> $payload */
        $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return $payload;
    }
}
