<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

/**
 * API Response Trait
 *
 * Provides standardized JSON response methods for API endpoints
 */
trait ApiResponseTrait
{
    /**
     * Return a successful JSON response
     *
     * @param  mixed  $data
     */
    protected function successResponse($data, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        $response['data'] = $data;

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response
     */
    protected function errorResponse(string $message, int $statusCode = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a not found JSON response
     */
    protected function notFoundResponse(?string $message = null): JsonResponse
    {
        return $this->errorResponse(
            $message ?? 'Resource not found',
            404
        );
    }

    /**
     * Return an unauthorized JSON response
     */
    protected function unauthorizedResponse(?string $message = null): JsonResponse
    {
        return $this->errorResponse(
            $message ?? 'Unauthorized',
            401
        );
    }

    /**
     * Return a forbidden JSON response
     */
    protected function forbiddenResponse(?string $message = null): JsonResponse
    {
        return $this->errorResponse(
            $message ?? 'Forbidden',
            403
        );
    }
}
