<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

final class JsonResponseFactory
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $status = JsonResponse::HTTP_OK): JsonResponse
    {
        $response = new JsonResponse($data, $status);
        $response->setEncodingOptions(
            JsonResponse::DEFAULT_ENCODING_OPTIONS
            | \JSON_PRETTY_PRINT
            | \JSON_UNESCAPED_UNICODE
            | \JSON_UNESCAPED_SLASHES,
        );

        return $response;
    }
}
