<?php

declare(strict_types=1);

namespace App\Tests\Integration\Order\Infrastructure;

use App\Order\Domain\Model\Order;
use App\Order\Domain\Model\OrderLine;
use App\Order\Infrastructure\Persistence\FileOrderRepository;
use PHPUnit\Framework\TestCase;

final class FileOrderRepositoryTest extends TestCase
{
    private string $storageDirectory;
    private const ORDER_ID = '0196254c-8ef5-7f62-9c7e-9a45c7392a18';

    protected function setUp(): void
    {
        $this->storageDirectory = sys_get_temp_dir().'/flowdog-orders-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        $this->removePath($this->storageDirectory);
    }

    public function testItPersistsAPrettyPrintedOrderJsonFile(): void
    {
        $repository = new FileOrderRepository($this->storageDirectory);
        $order = $this->createOrder();

        $repository->save($order);

        $file = $this->storageDirectory.'/'.self::ORDER_ID.'.json';
        self::assertFileExists($file);
        $content = file_get_contents($file);
        self::assertIsString($content);
        self::assertStringContainsString(PHP_EOL.'    "customerId": 123,', $content);
        $temporaryFiles = glob($this->storageDirectory.'/order_*');
        if (false === $temporaryFiles) {
            $temporaryFiles = [];
        }

        self::assertSame([], $temporaryFiles);
    }

    public function testItThrowsWhenTargetFileAlreadyExistsAndDoesNotOverwriteIt(): void
    {
        $repository = new FileOrderRepository($this->storageDirectory);
        mkdir($this->storageDirectory, 0777, true);
        $targetFile = $this->storageDirectory.'/'.self::ORDER_ID.'.json';
        file_put_contents($targetFile, "{\"existing\":true}\n");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Order "%s" could not be saved.', self::ORDER_ID));

        try {
            $repository->save($this->createOrder());
        } finally {
            self::assertSame("{\"existing\":true}\n", file_get_contents($targetFile));
        }
    }

    private function createOrder(): Order
    {
        return Order::place(
            id: self::ORDER_ID,
            customerId: 123,
            lines: [OrderLine::fromCatalogProduct(new \App\Order\Domain\Model\CatalogProduct(10, 'Keyboard', 120, 5, true), 2)],
            createdAt: new \DateTimeImmutable('2026-04-11T15:00:00+00:00'),
            couponCode: 'PROMO10',
        );
    }

    private function removePath(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            unlink($path);

            return;
        }

        $entries = scandir($path);
        if (false === $entries) {
            return;
        }

        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $this->removePath($path.'/'.$entry);
        }

        rmdir($path);
    }
}
