<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use App\Shared\Domain\Exception\DomainError;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private JsonResponseFactory $jsonResponseFactory)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof HttpExceptionInterface) {
            $apiError = $this->mapHttpException($throwable);
            if (null !== $apiError) {
                $event->setResponse(
                    $this->jsonResponseFactory->create(
                        $apiError->toArray(),
                        $throwable->getStatusCode(),
                    ),
                );

                return;
            }
        }

        if ($throwable instanceof DomainError) {
            $errorCode = $throwable->errorCode();

            $event->setResponse(
                $this->jsonResponseFactory->create(
                    (new ApiErrorData(
                        code: $errorCode,
                        message: $throwable->getMessage(),
                    ))->toArray(),
                    $this->resolveStatusCode($errorCode),
                ),
            );

            return;
        }

        if ($throwable instanceof ExtraAttributesException) {
            $event->setResponse(
                $this->jsonResponseFactory->create(
                    (new ApiErrorData(
                        code: 'invalid_request_payload',
                        message: $this->createInvalidRequestPayloadMessage($throwable),
                    ))->toArray(),
                    Response::HTTP_BAD_REQUEST,
                ),
            );

            return;
        }

        $event->setResponse(
            $this->jsonResponseFactory->create(
                (new ApiErrorData(
                    code: 'internal_error',
                    message: 'Internal server error.',
                ))->toArray(),
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ),
        );
    }

    private function mapHttpException(HttpExceptionInterface $throwable): ?ApiErrorData
    {
        $previous = $throwable->getPrevious();
        if ($previous instanceof ValidationFailedException) {
            return $this->mapValidationFailure($previous);
        }

        $statusCode = $throwable->getStatusCode();

        if (Response::HTTP_BAD_REQUEST === $statusCode) {
            if (str_contains($throwable->getMessage(), 'invalid "json" data')) {
                return new ApiErrorData('malformed_json', 'Request body must contain valid JSON.');
            }

            return new ApiErrorData(
                'invalid_request_payload',
                '' !== $throwable->getMessage() ? $throwable->getMessage() : 'Request payload is invalid.',
            );
        }

        if (Response::HTTP_UNSUPPORTED_MEDIA_TYPE === $statusCode) {
            return new ApiErrorData(
                'unsupported_media_type',
                '' !== $throwable->getMessage() ? $throwable->getMessage() : 'Unsupported format.',
            );
        }

        if ($throwable instanceof NotFoundHttpException) {
            return new ApiErrorData('route_not_found', 'Route not found.');
        }

        if ($throwable instanceof MethodNotAllowedHttpException) {
            return new ApiErrorData('method_not_allowed', 'Method not allowed.');
        }

        if ($statusCode >= Response::HTTP_BAD_REQUEST && $statusCode < Response::HTTP_INTERNAL_SERVER_ERROR) {
            return new ApiErrorData(
                'client_error',
                Response::$statusTexts[$statusCode] ?? 'Client error.',
            );
        }

        return null;
    }

    private function mapValidationFailure(ValidationFailedException $exception): ApiErrorData
    {
        $violation = $exception->getViolations()[0] ?? null;
        if (!$violation instanceof ConstraintViolationInterface) {
            return new ApiErrorData('validation_failed', 'Validation failed.');
        }

        $errorCode = $this->resolveValidationErrorCode($violation);
        $message = 'missing_field' === $errorCode
            ? sprintf('Field "%s" is required.', $this->normalizePropertyPath($violation->getPropertyPath()))
            : (string) $violation->getMessage();

        return new ApiErrorData($errorCode, $message);
    }

    private function resolveValidationErrorCode(ConstraintViolationInterface $violation): string
    {
        if (Collection::NO_SUCH_FIELD_ERROR === $violation->getCode()) {
            return 'invalid_items';
        }

        if (Collection::MISSING_FIELD_ERROR === $violation->getCode()) {
            return 'missing_field';
        }

        $payload = $violation->getConstraint()?->payload;
        if (\is_array($payload) && isset($payload['error_code']) && \is_string($payload['error_code'])) {
            return $payload['error_code'];
        }

        return 'validation_failed';
    }

    private function createInvalidRequestPayloadMessage(ExtraAttributesException $exception): string
    {
        $extraAttributes = array_values(
            array_filter(
                $exception->getExtraAttributes(),
                static fn (mixed $attribute): bool => \is_string($attribute) && '' !== $attribute,
            ),
        );

        if (1 === \count($extraAttributes)) {
            return sprintf('Request payload contains invalid "%s" property.', $extraAttributes[0]);
        }

        if ([] !== $extraAttributes) {
            return sprintf(
                'Request payload contains invalid properties: "%s".',
                implode('", "', $extraAttributes),
            );
        }

        return 'Request payload is invalid.';
    }

    private function normalizePropertyPath(string $propertyPath): string
    {
        $normalized = preg_replace_callback(
            '/\[([^\]]+)\]/',
            static fn (array $matches): string => ctype_digit($matches[1]) ? '['.$matches[1].']' : '.'.$matches[1],
            $propertyPath,
        );

        if (null === $normalized || '' === $normalized) {
            return 'payload';
        }

        return ltrim($normalized, '.');
    }

    private function resolveStatusCode(string $errorCode): int
    {
        return match ($errorCode) {
            'invalid_customer_id',
            'invalid_items',
            'invalid_quantity',
            'invalid_coupon' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'product_not_found' => Response::HTTP_NOT_FOUND,
            'inactive_product',
            'insufficient_stock' => Response::HTTP_CONFLICT,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }
}
