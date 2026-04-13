<?php

declare(strict_types=1);

namespace App\Tests\Characterization;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/legacy/OrderController.php';

final class LegacyOrderControllerCharacterizationTest extends TestCase
{
    private string $originalDirectory;
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $currentDirectory = getcwd();
        self::assertIsString($currentDirectory);
        $this->originalDirectory = $currentDirectory;
        $this->temporaryDirectory = sys_get_temp_dir().'/flowdog-legacy-'.bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory, 0777, true);
        chdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        chdir($this->originalDirectory);
        $this->deleteDirectory($this->temporaryDirectory);
    }

    public function testItPreservesLineTotalAndTotalCalculation(): void
    {
        $response = $this->controller()->create([
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 2],
            ],
        ]);

        $data = $response['data'];
        self::assertIsArray($data);
        /** @var array{items: list<array{lineTotal:int}>, total:int} $data */
        self::assertSame(240, $data['items'][0]['lineTotal']);
        self::assertSame(240, $data['total']);
    }

    public function testItPreservesPromo10CouponBehavior(): void
    {
        $response = $this->controller()->create([
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 2],
            ],
            'couponCode' => 'PROMO10',
        ]);

        $data = $response['data'];
        self::assertIsArray($data);
        /** @var array{total: float} $data */
        self::assertSame(216.0, $data['total']);
    }

    public function testItPreservesMinus50CouponBehaviorAboveThreshold(): void
    {
        $response = $this->controller()->create([
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 3],
            ],
            'couponCode' => 'MINUS50',
        ]);

        $data = $response['data'];
        self::assertIsArray($data);
        /** @var array{total: int} $data */
        self::assertSame(310, $data['total']);
    }

    public function testItPreservesInvalidQuantityRejection(): void
    {
        $response = $this->controller()->create([
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 0],
            ],
        ]);

        self::assertSame('Invalid quantity', $response['message']);
    }

    public function testItPreservesInactiveProductRejection(): void
    {
        $response = $this->controller()->create([
            'customerId' => 123,
            'items' => [
                ['productId' => 20, 'quantity' => 1],
            ],
        ]);

        self::assertSame('Product inactive', $response['message']);
    }

    public function testItPreservesStockRejection(): void
    {
        $response = $this->controller()->create([
            'customerId' => 123,
            'items' => [
                ['productId' => 15, 'quantity' => 1],
            ],
        ]);

        self::assertSame('Not enough stock', $response['message']);
    }

    public function testItPreservesBasicOrderPayloadShapeForSuccessfulOrders(): void
    {
        $response = $this->controller()->create([
            'customerId' => 123,
            'items' => [
                ['productId' => 10, 'quantity' => 1],
            ],
        ]);

        self::assertArrayHasKey('data', $response);
        self::assertIsArray($response['data']);
        $data = $response['data'];
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('customerId', $data);
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('total', $data);
        self::assertArrayHasKey('createdAt', $data);
    }

    private function controller(): \OrderController
    {
        return new \OrderController();
    }

    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $directory.'/'.$item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
