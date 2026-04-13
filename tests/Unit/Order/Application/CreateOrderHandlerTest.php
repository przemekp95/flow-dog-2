<?php

declare(strict_types=1);

namespace App\Tests\Unit\Order\Application;

use App\Order\Application\CreateOrder\CreateOrderCommand;
use App\Order\Application\CreateOrder\CreateOrderHandler;
use App\Order\Application\CreateOrder\CreateOrderItemCommand;
use App\Order\Application\Port\EventBus;
use App\Order\Application\Port\OrderRepository;
use App\Order\Application\Port\ProductCatalog;
use App\Order\Domain\Event\OrderCreated;
use App\Order\Domain\Exception\InvalidItems;
use App\Order\Domain\Model\CatalogProduct;
use App\Order\Domain\Model\Order;
use App\Order\Infrastructure\Catalog\InMemoryProductCatalog;
use App\Tests\Support\CollectingEventBus;
use App\Tests\Support\FixedClock;
use App\Tests\Support\InMemoryOrderRepository;
use App\Tests\Support\StaticIdGenerator;
use PHPUnit\Framework\TestCase;

final class CreateOrderHandlerTest extends TestCase
{
    public function testItCreatesAnOrderFromValidInput(): void
    {
        $repository = new InMemoryOrderRepository();
        $eventBus = new CollectingEventBus();
        $handler = $this->createHandler($repository, $eventBus);

        $result = $handler(new CreateOrderCommand(
            customerId: 123,
            items: [
                new CreateOrderItemCommand(10, 2),
            ],
            couponCode: 'PROMO10',
        ));

        self::assertSame('0196254c-8ef5-7f62-9c7e-9a45c7392a18', $result->id);
        self::assertSame(216, $result->total);
        self::assertNotNull($repository->get('0196254c-8ef5-7f62-9c7e-9a45c7392a18'));
        self::assertCount(1, $eventBus->events());
        self::assertInstanceOf(OrderCreated::class, $eventBus->events()[0]);
    }

    public function testItRejectsDuplicateProductsBeforeFetchingTheCatalog(): void
    {
        $repository = new InMemoryOrderRepository();
        $eventBus = new CollectingEventBus();
        $productCatalog = new class implements ProductCatalog {
            public int $calls = 0;

            public function getById(int $productId): CatalogProduct
            {
                ++$this->calls;

                throw new \RuntimeException('Product catalog should not be queried for duplicate items.');
            }
        };
        $handler = $this->createHandler($repository, $eventBus, $productCatalog);

        try {
            $handler(new CreateOrderCommand(
                customerId: 123,
                items: [
                    new CreateOrderItemCommand(10, 1),
                    new CreateOrderItemCommand(10, 2),
                ],
                couponCode: null,
            ));
            self::fail('Expected InvalidItems to be thrown for duplicate product ids.');
        } catch (InvalidItems $exception) {
            self::assertSame('invalid_items', $exception->errorCode());
            self::assertSame('Each product can appear only once in items.', $exception->getMessage());
        }

        self::assertSame([], $repository->orders);
        self::assertSame([], $eventBus->events());
        self::assertSame(0, $productCatalog->calls);
    }

    public function testItDoesNotPublishEventWhenSavingOrderFails(): void
    {
        $eventBus = new CollectingEventBus();
        $handler = $this->createHandler(
            new class implements OrderRepository {
                public function save(Order $order): void
                {
                    throw new \RuntimeException('Disk full.');
                }
            },
            $eventBus,
        );

        try {
            $handler(new CreateOrderCommand(
                customerId: 123,
                items: [
                    new CreateOrderItemCommand(10, 2),
                ],
                couponCode: null,
            ));
            self::fail('Expected RuntimeException to be thrown when saving the order fails.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Disk full.', $exception->getMessage());
        }

        self::assertSame([], $eventBus->events());
    }

    public function testItReturnsSuccessWhenPublishingEventFailsAfterSavingOrder(): void
    {
        $repository = new InMemoryOrderRepository();
        $eventBus = new class implements EventBus {
            public int $calls = 0;

            public function publish(object $event): void
            {
                ++$this->calls;

                throw new \RuntimeException('Listener failed.');
            }
        };
        $handler = $this->createHandler($repository, $eventBus);

        $result = $handler(new CreateOrderCommand(
            customerId: 123,
            items: [
                new CreateOrderItemCommand(10, 2),
            ],
            couponCode: null,
        ));

        self::assertSame('0196254c-8ef5-7f62-9c7e-9a45c7392a18', $result->id);
        self::assertSame(240, $result->total);
        self::assertNotNull($repository->get('0196254c-8ef5-7f62-9c7e-9a45c7392a18'));
        self::assertSame(1, $eventBus->calls);
    }

    private function createHandler(
        OrderRepository $repository,
        EventBus $eventBus,
        ?ProductCatalog $productCatalog = null,
    ): CreateOrderHandler {
        return new CreateOrderHandler(
            $productCatalog ?? new InMemoryProductCatalog(),
            $repository,
            $eventBus,
            new FixedClock(new \DateTimeImmutable('2026-04-11T15:00:00+00:00')),
            new StaticIdGenerator('0196254c-8ef5-7f62-9c7e-9a45c7392a18'),
        );
    }
}
