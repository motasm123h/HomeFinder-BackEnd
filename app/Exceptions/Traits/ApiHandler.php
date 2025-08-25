<?php

namespace App\Exceptions\Traits;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

trait ApiHandler
{
    protected function handleApiException(\Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ModelNotFoundException => $this->apiError(
                'Resource not found',
                404,
                'not_found'
            ),
            $e instanceof ValidationException => $this->apiError(
                'Validation failed',
                422,
                'validation_error',
                ['errors' => $e->errors()]
            ),
            $e instanceof HttpExceptionInterface => $this->apiError(
                $e->getMessage(),
                $e->getStatusCode(),
                'http_error'
            ),
            default => $this->apiError(
                config('app.debug') ? $e->getMessage() : 'Server error',
                500,
                'server_error'
            )
        };
    }

    protected function apiError(
        string $message,
        int $code,
        string $errorCode,
        array $data = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'data' => $data,
        ], $code);
    }
}
