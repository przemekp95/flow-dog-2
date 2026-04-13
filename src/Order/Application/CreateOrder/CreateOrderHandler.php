<?php

declare(strict_types=1);

namespace App\Order\Application\CreateOrder;

use App\Order\Application\Port\Clock;
use App\Order\Application\Port\EventBus;
use App\Order\Application\Port\IdGenerator;
use App\Order\Application\Port\OrderRepository;
use App\Order\Application\Port\ProductCatalog;
use App\Order\Domain\Event\OrderCreated;
use App\Order\Domain\Exception\InvalidItems;
use App\Order\Domain\Model\Order;
use App\Order\Domain\Model\OrderLine;

final readonly class CreateOrderHandler
{
    public function __construct(
        private ProductCatalog $productCatalog,
        private OrderRepository $orderRepository,
        private EventBus $eventBus,
        private Clock $clock,
        private IdGenerator $idGenerator,
    ) {
    }

    public function __invoke(CreateOrderCommand $command): CreateOrderResult
    {
        self::assertItemsContainUniqueProducts($command->items);

        $lines = [];

        foreach ($command->items as $item) {
            $product = $this->productCatalog->getById($item->productId);
            $lines[] = OrderLine::fromCatalogProduct($product, $item->quantity);
        }

        $order = Order::place(
            id: $this->idGenerator->generate(),
            customerId: $command->customerId,
            lines: $lines,
            createdAt: $this->clock->now(),
            couponCode: $command->couponCode,
        );

        $this->orderRepository->save($order);
        $this->publishOrderCreatedBestEffort($order);

        return CreateOrderResult::fromOrder($order);
    }

    /**
     * @param list<CreateOrderItemCommand> $items
     */
    private static function assertItemsContainUniqueProducts(array $items): void
    {
        $productIds = array_map(
            static fn (CreateOrderItemCommand $item): int => $item->productId,
            $items,
        );

        if (\count($productIds) === \count(array_unique($productIds))) {
            return;
        }

        throw new InvalidItems('Each product can appear only once in items.');
    }

    private function publishOrderCreatedBestEffort(Order $order): void
    {
        try {
            $this->eventBus->publish(OrderCreated::fromOrder($order));
        } catch (\Throwable) {
            // Persistence wins here; introducing an outbox is intentionally out of scope for this repo.
        }
    }
}
