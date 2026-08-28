<?php

namespace App\Http\Controllers\concerns;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    public function success($data = [], $message = 'Operation completed successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => [],
        ], 200);
    }

    public function unauthorized($message = 'Unauthorized Request'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => [],
        ], 401);
    }

    public function notFound($message = 'not found'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => [],
        ], 404);
    }

    public function tooManyRequests(string $message = 'Too many requests. Please try again later.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => [],
        ], 429);
    }

    public function error(string $message = 'An error occurred', int $statusCode = 500, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $statusCode);
    }

    public function validationError($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    public function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => [],
        ], 403);
    }
}
