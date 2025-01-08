<?php

namespace App\HttpModels;

interface HttpModel
{
    public function __construct(array $data);

    /**
     * Validate and format data for advertiser
     */
    public function validateAndFormat(array $data): array;

    /**
     * Return the formatted data as an array
     */
    public function toArray(): array;

    /**
     * Create an instance from API response (format incoming data)
     */
    public static function fromApiResponse(array $response): self;
}
