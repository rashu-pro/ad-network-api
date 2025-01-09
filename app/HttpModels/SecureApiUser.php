<?php

namespace App\HttpModels;

use App\Enums\CompanyCategory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class SecureApiUser implements HttpModel
{
    protected array $data;

    public function __construct(array $data, bool $requireCompanyKey = false)
    {
        $this->data = $this->validateAndFormat($data,$requireCompanyKey);
    }

    /**
     * Validate and format data for advertiser
     * @throws ValidationException
     */
    public function validateAndFormat(array $data, bool $requireCompanyKey = false): array
    {
        // Base validation rules
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'logo_url' => 'required|string',
            'password' => 'nullable|confirmed',
            'line1' => 'required|string',
            'line2' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zipCode' => 'required|string',
            'country' => 'required|string',
            'latitude' => 'numeric|required',
            'longitude' => 'numeric|required',
            'advertiser_website' => 'string|max:255|required',
            'advertiser_phone' => 'string|max:255|required',
            'companyCategory' => ['required', new Enum(CompanyCategory::class)]
        ];
        if ($data['companyCategory'] === CompanyCategory::ADVERTISER) {
            $rules['email'] = 'required|email|max:255|unique:advertisers,email';
        } else {
            $rules['email'] = 'required|email|max:255|unique:publishers,email';
        }

        // Add rule for secure_api_id (companyKey) if required
        if ($requireCompanyKey) {
            $rules['secure_api_id'] = 'required|string';
        } else {
            $rules['secure_api_id'] = 'nullable|string';
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Formatting the validated data
        return [
            "secure_api_id" => $data["secure_api_id"] ?? null,
            "companyCategory" => $data["companyCategory"] ?? "AdPublisher",
            "businessName" => $data["business_name"] ?? "",
            "businessInfo" => [
                "websiteUrl" => $data["advertiser_website"] ?? "",
                "logoUrl" => $data["logo_url"] ?? "",
                "address" => [
                    "line1" => $data["line1"],
                    "line2" => $data["line2"],
                    "city" => $data["city"],
                    "state" => $data["state"],
                    "zipCode" => $data["zipCode"],
                    "country" => $data["country"],
                    "latitude" => $data["latitude"] ?? 0,
                    "longitude" => $data["longitude"] ?? 0,
                ]
            ],
            "contactInfo" => [
                "name" => "{$data['first_name']} {$data['last_name']}",
                "email" => $data["email"],
                "phone" => $data["advertiser_phone"] ?? "",
            ],
        ];
    }


    /**
     * Return the formatted data as an array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Create an instance from API response (format incoming data)
     */
    public static function fromApiResponse(array $response): self
    {
        return new self([
            "secure_api_id" => $response["companyKey"],
            "companyCategory" => $response["companyCategory"],
            "business_name" => $response["businessName"],
            "advertiser_website" => $response["businessInfo"]["websiteUrl"],
            "logo_url" => $response["businessInfo"]["logoUrl"] ?? '',
            "line1" => $response["businessInfo"]["address"]["line1"],
            "line2" => $response["businessInfo"]["address"]["line2"] ?? 'N\A',
            "city" => $response["businessInfo"]["address"]["city"],
            "state" => $response["businessInfo"]["address"]["state"],
            "zipCode" => $response["businessInfo"]["address"]["zipCode"],
            "country" => $response["businessInfo"]["address"]["country"],
            "latitude" => $response["businessInfo"]["address"]["latitude"],
            "longitude" => $response["businessInfo"]["address"]["longitude"],
            "first_name" => explode(' ', $response["contactInfo"]["name"])[0] ?? "",
            "last_name" => explode(' ', $response["contactInfo"]["name"])[1] ?? "",
            "email" => $response["contactInfo"]["email"],
            "advertiser_phone" => $response["contactInfo"]["phone"],
        ],true);
    }
}
