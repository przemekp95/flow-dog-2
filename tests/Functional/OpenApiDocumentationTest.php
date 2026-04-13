<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OpenApiDocumentationTest extends WebTestCase
{
    public function testSwaggerUiIsAvailable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/doc');

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('id="swagger-ui"', $content);
    }

    public function testOpenapiJsonIsAvailableAndPrettyPrinted(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/doc.json');

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString(PHP_EOL.'    "paths": {', $content);

        /** @var array{
         *     paths: array<string, mixed>,
         *     servers: list<array{url: string}>,
         *     tags: list<array{name: string}>,
         *     components: array{schemas: array<string, mixed>}
         * } $payload
         */
        $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('/orders', $payload['paths']);
        /** @var array{post: array{
         *     requestBody: array{
         *         content: array{
         *             'application/json': array{
         *                 schema: array{'$ref': string}
         *             }
         *         }
         *     },
         *     responses: array<int|string, mixed>
         * }} $ordersPath
         */
        $ordersPath = $payload['paths']['/orders'];
        $postOperation = $ordersPath['post'];
        self::assertSame(
            '#/components/schemas/CreateOrderRequestData',
            $postOperation['requestBody']['content']['application/json']['schema']['$ref'],
        );
        $responses = $postOperation['responses'];
        self::assertArrayHasKey(201, $responses);
        self::assertArrayHasKey(400, $responses);
        self::assertArrayHasKey(404, $responses);
        self::assertArrayHasKey(409, $responses);
        self::assertArrayHasKey(415, $responses);
        self::assertArrayHasKey(422, $responses);
        self::assertArrayHasKey(500, $responses);
        /** @var array{
         *     description: string,
         *     content: array{
         *         'application/json': array{
         *             examples: array{
         *                 invalid_request_payload: array{
         *                     value: array{code: string}
         *                 }
         *             }
         *         }
         *     }
         * } $response400
         */
        $response400 = $responses[400];
        /** @var array{
         *     content: array{
         *         'application/json': array{
         *             examples: array{
         *                 unsupported_media_type: array{
         *                     value: array{code: string}
         *                 }
         *             }
         *         }
         *     }
         * } $response415
         */
        $response415 = $responses[415];
        self::assertSame('Malformed JSON or invalid request payload.', $response400['description']);
        self::assertSame('invalid_request_payload', $response400['content']['application/json']['examples']['invalid_request_payload']['value']['code']);
        self::assertSame('unsupported_media_type', $response415['content']['application/json']['examples']['unsupported_media_type']['value']['code']);
        self::assertSame([['url' => 'http://localhost:8080']], $payload['servers']);
        self::assertSame('Orders', $payload['tags'][0]['name']);

        /** @var array{
         *     type: string,
         *     additionalProperties: bool,
         *     properties: array{
         *         customerId: array{minimum: int, exclusiveMinimum: bool},
         *         items: array{
         *             minItems: int,
         *             items: array{
         *                 type: string,
         *                 additionalProperties: bool,
         *                 properties: array{
         *                     productId: array{minimum: int},
         *                     quantity: array{minimum: int}
         *                 }
         *             }
         *         }
         *     }
         * } $requestSchema
         */
        $requestSchema = $payload['components']['schemas']['CreateOrderRequestData'];
        self::assertSame('object', $requestSchema['type']);
        self::assertFalse($requestSchema['additionalProperties']);
        self::assertSame(0, $requestSchema['properties']['customerId']['minimum']);
        self::assertTrue($requestSchema['properties']['customerId']['exclusiveMinimum']);
        self::assertSame(1, $requestSchema['properties']['items']['minItems']);
        self::assertSame('object', $requestSchema['properties']['items']['items']['type']);
        self::assertFalse($requestSchema['properties']['items']['items']['additionalProperties']);
        self::assertSame(1, $requestSchema['properties']['items']['items']['properties']['productId']['minimum']);
        self::assertSame(1, $requestSchema['properties']['items']['items']['properties']['quantity']['minimum']);
        self::assertArrayNotHasKey('CreateOrderRequest', $payload['components']['schemas']);
        self::assertArrayNotHasKey('CreateOrderItemRequest', $payload['components']['schemas']);
    }
}
