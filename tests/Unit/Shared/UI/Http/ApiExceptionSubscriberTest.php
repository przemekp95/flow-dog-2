<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\UI\Http;

use App\Shared\UI\Http\ApiExceptionSubscriber;
use App\Shared\UI\Http\JsonResponseFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ApiExceptionSubscriberTest extends TestCase
{
    public function testItWrapsUnhandledClientErrorsIntoJson(): void
    {
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/forbidden'),
            HttpKernelInterface::MAIN_REQUEST,
            new HttpException(Response::HTTP_FORBIDDEN),
        );

        $subscriber = new ApiExceptionSubscriber(new JsonResponseFactory());
        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        /** @var array{code:string, message:string} $payload */
        $payload = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame(
            [
                'code' => 'client_error',
                'message' => 'Forbidden',
            ],
            $payload,
        );
    }
}
