<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class SecureApiException extends Exception
{
    protected int $statusCode;
    protected ?string $responseBody;

    public function __construct(string $message, int $statusCode = 0, ?string $responseBody = null)
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;

        // Log the exception details automatically
        $this->logException();
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    private function logException(): void
    {
        Log::error('SecureApiServiceException', [
            'message' => $this->getMessage(),
            'status_code' => $this->statusCode,
            'response_body' => $this->responseBody,
            'trace' => $this->getTraceAsString(),
        ]);
    }
}
