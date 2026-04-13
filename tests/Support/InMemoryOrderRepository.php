<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Order\Application\Port\OrderRepository;
use App\Order\Domain\Model\Order;

final class InMemoryOrderRepository implements OrderRepository
{
    /**
     * @var list<Order>
     */
    public array $orders = [];

    public function save(Order $order): void
    {
        $this->orders[] = $order;
    }

    public function get(string $id): ?Order
    {
        foreach ($this->orders as $order) {
            if ($order->id === $id) {
                return $order;
            }
        }

        return null;
    }
}
