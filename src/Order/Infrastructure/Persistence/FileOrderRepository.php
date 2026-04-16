<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence;

use App\Order\Application\Port\OrderRepository;
use App\Order\Domain\Model\Order;

final readonly class FileOrderRepository implements OrderRepository
{
    public function __construct(
        private string $storageDirectory,
    ) {
    }

    public function save(Order $order): void
    {
        $this->ensureStorageDirectoryExists();

        $targetFile = $this->storageDirectory.'/'.$order->id.'.json';
        if (file_exists($targetFile)) {
            throw new \RuntimeException(sprintf('Order "%s" could not be saved.', $order->id));
        }

        $temporaryFile = tempnam($this->storageDirectory, 'order_');
        if (false === $temporaryFile) {
            throw new \RuntimeException(sprintf('Order "%s" could not be saved.', $order->id));
        }

        try {
            $bytesWritten = file_put_contents(
                $temporaryFile,
                json_encode(
                    $this->normalizeOrder($order),
                    \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR,
                ).PHP_EOL,
                LOCK_EX,
            );

            // Create the final file atomically without allowing overwrites.
            if (false === $bytesWritten || !link($temporaryFile, $targetFile) || !unlink($temporaryFile)) {
                throw new \RuntimeException(sprintf('Order "%s" could not be saved.', $order->id));
            }

            $temporaryFile = null;
        } finally {
            if (null !== $temporaryFile && file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    /**
     * @return array{id: string, customerId: int, items: list<array{productId:int, name:string, quantity:int, price:int, lineTotal:int}>, total: int, createdAt: string, couponCode?: string}
     */
    private function normalizeOrder(Order $order): array
    {
        $payload = [
            'id' => $order->id,
            'customerId' => $order->customerId,
            'items' => array_map(
                static fn (\App\Order\Domain\Model\OrderLine $line): array => [
                    'productId' => $line->productId,
                    'name' => $line->name,
                    'quantity' => $line->quantity,
                    'price' => $line->price,
                    'lineTotal' => $line->lineTotal,
                ],
                $order->items,
            ),
            'total' => $order->total,
            'createdAt' => $order->createdAt->format(\DATE_ATOM),
        ];

        if (null !== $order->couponCode) {
            $payload['couponCode'] = $order->couponCode;
        }

        return $payload;
    }

    private function ensureStorageDirectoryExists(): void
    {
        if (is_dir($this->storageDirectory)) {
            return;
        }

        if (!mkdir($concurrentDirectory = $this->storageDirectory, 0775, true) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created.', $this->storageDirectory));
        }
    }
}
