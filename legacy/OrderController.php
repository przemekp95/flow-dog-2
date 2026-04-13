<?php

class OrderController
{
    public function create(array $request): array
    {
        if (!isset($request['customerId'])) {
            return ['status' => 500, 'message' => 'Customer missing'];
        }

        if (!isset($request['items'])) {
            return ['status' => 500, 'message' => 'Items missing'];
        }

        $products = [
            10 => ['id' => 10, 'name' => 'Keyboard', 'price' => 120, 'stock' => 5, 'active' => true],
            15 => ['id' => 15, 'name' => 'Mouse', 'price' => 80, 'stock' => 0, 'active' => true],
            20 => ['id' => 20, 'name' => 'Monitor', 'price' => 900, 'stock' => 2, 'active' => false],
        ];

        $total = 0;
        $items = [];

        foreach ($request['items'] as $item) {
            $product = $products[$item['productId']] ?? null;

            if (!$product) {
                return ['status' => 500, 'message' => 'Product not found'];
            }

            if (!$product['active']) {
                return ['status' => 500, 'message' => 'Product inactive'];
            }

            if ($item['quantity'] <= 0) {
                return ['status' => 500, 'message' => 'Invalid quantity'];
            }

            if ($item['quantity'] > $product['stock']) {
                return ['status' => 500, 'message' => 'Not enough stock'];
            }

            $lineTotal = $product['price'] * $item['quantity'];
            $total += $lineTotal;

            $items[] = [
                'productId' => $product['id'],
                'name' => $product['name'],
                'quantity' => $item['quantity'],
                'price' => $product['price'],
                'lineTotal' => $lineTotal,
            ];
        }

        if (isset($request['couponCode'])) {
            if ($request['couponCode'] === 'PROMO10') {
                $total = $total - ($total * 0.1);
            }

            if ($request['couponCode'] === 'MINUS50') {
                if ($total >= 300) {
                    $total = $total - 50;
                }
            }

            if ($request['couponCode'] === 'FREEMONEY') {
                $total = -9999;
            }
        }

        $order = [
            'id' => rand(1000, 9999),
            'customerId' => $request['customerId'],
            'items' => $items,
            'total' => $total,
            'createdAt' => date('Y-m-d H:i:s'),
        ];

        file_put_contents('orders.log', json_encode($order).PHP_EOL, FILE_APPEND);

        return [
            'status' => 200,
            'data' => $order,
        ];
    }
}
