<?php

declare(strict_types=1);

namespace App\Order\Application\Port;

use App\Order\Domain\Model\Order;

interface OrderRepository
{
    public function save(Order $order): void;
}
