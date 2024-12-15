<?php

namespace App\Traits;


use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    public function successResponse(string $message, array $data = [], int $status = 200) : JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data ?? [],
        ], $status);
    }
    public function errorResponse(string $message, array $errors = [], int $status = 500) : JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors ?? [],
        ], $status);
    }
}
