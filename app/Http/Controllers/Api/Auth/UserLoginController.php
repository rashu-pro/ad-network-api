<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\RolesEnum;
use App\Enums\TokenAbility;
use App\Events\AdvertiserRegistered;
use App\Facades\SecureApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdvertiserLoginRequest;
use App\Models\Advertiser;
use App\Models\CampaignMapping;
use App\Models\CampaignPayment;
use App\Models\User;
use App\Services\BillingService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Role;

class UserLoginController extends Controller
{
    use ApiResponse;

    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    #[OA\Post(
        path: "/api/advertiser/login",
        summary: "Advertiser login",
        requestBody: new OA\RequestBody(required: true,
            content: new OA\MediaType(mediaType: "application/x-www-form-urlencoded",
                schema: new OA\Schema(required: ["email", "password"],
                    properties: [
                        new OA\Property(property: 'email', description: "User email", type: "string"),
                        new OA\Property(property: 'password', description: "User password", type: "string"),
                    ]
                ))),
        tags: ["**Authentication::Advertiser**"],
        responses: [
            new OA\Response(response: 200, description: "Login successful",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: true),
                        new OA\Property(property: 'message', type: "string", example: "Logged in successfully."),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'access_token', type: "string"),
                            new OA\Property(property: 'access_token_expires_at', type: "string", format: "date-time"),
                            new OA\Property(property: 'refresh_token', type: "string"),
                            new OA\Property(property: 'refresh_token_expires_at', type: "string", format: "date-time"),
                            new OA\Property(property: 'token_type', type: "string", example: "Bearer"),
                        ],
                            type: "object"
                        )
                    ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 422, description: "Validation error",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: false),
                        new OA\Property(property: 'message', type: "string", example: "Validation Error"),
                        new OA\Property(property: 'errors', type: "object"),
                    ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 500, description: "Internal Server Error",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: false),
                        new OA\Property(property: 'message', type: "string", example: "Server Error"),
                        new OA\Property(property: 'errors', type: "object", nullable: true),
                    ],
                        type: "object"
                    )
                )
            )
        ]
    )]
    public function login(Request $request)
    {
        try {
            $res = SecureApi::login([
                'grant_type' => 'password',
                'username' => $request->email,
                'password' => $request->password
            ]);

            Log::info('response from secure api ', $res);

            $user = json_decode($res['user']);
            $localUser = User::where('email', $user->email)
                ->where('secure_api_id', $user->companyKey)
                ->first();
            $userRoles = explode(',', $user->roles);

            if (!$localUser) {
                if(! in_array('AdNetworkCompanyAdmin', $userRoles)){
                    return $this->errorResponse('User is not a AdNetworkCompanyAdmin');
                }
                $localUser = User::create([
                    'email' => $user->email,
                    'secure_api_id' => $user->companyKey
                ]);


                if($user->isAdPublisher == true){
                    $publisherRole = app(Role::class)->findOrCreate(RolesEnum::PUBLISHER->value,'api');
                    $localUser->assignRole($publisherRole);
                }

                if($user->isAdvertiser == true){
                    $advertiserRole = app(Role::class)->findOrCreate(RolesEnum::ADVERTISER->value,'api');
                    $localUser->assignRole($advertiserRole);
                    event(new AdvertiserRegistered($localUser));
                }
            }
            return $this->createTokens($localUser);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    #[OA\Post(
        path: "/api/advertiser/get-access-token",
        summary: "Advertiser access token with refresh token",
        security: [
            ["bearerAuth" => []], // For JWT bearer tokens
            ["sanctum" => []],    // For Sanctum API keys
        ],
        tags: ["**Authentication::Advertiser**"],
        responses: [
            new OA\Response(response: 200, description: "Access token generated successfully",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: true),
                        new OA\Property(property: 'message', type: "string", example: "Access token generated successfully."),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'access_token', type: "string"),
                            new OA\Property(property: 'access_token_expires_at', type: "string", format: "date-time"),
                            new OA\Property(property: 'refresh_token', type: "string"),
                            new OA\Property(property: 'refresh_token_expires_at', type: "string", format: "date-time"),
                            new OA\Property(property: 'token_type', type: "string", example: "Bearer"),
                        ],
                            type: "object"
                        )
                    ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 422, description: "Validation error",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: false),
                        new OA\Property(property: 'message', type: "string", example: "Validation Error"),
                        new OA\Property(property: 'errors', type: "object"),
                    ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 500, description: "Internal Server Error",
                content: new OA\MediaType(mediaType: "application/json",
                    schema: new OA\Schema(properties: [
                        new OA\Property(property: 'success', type: "boolean", example: false),
                        new OA\Property(property: 'message', type: "string", example: "Server Error"),
                        new OA\Property(property: 'errors', type: "object", nullable: true),
                    ],
                        type: "object"
                    )
                )
            )
        ]
    )]
    public function refresh(Request $request)
    {
        try {
            $refreshToken = $request->bearerToken();

            if (!$refreshToken) {
                throw ValidationException::withMessages([
                    'refresh_token' => ['Refresh token is required.'],
                ]);
            }
            $token = PersonalAccessToken::findToken($refreshToken);

            if (!$token || !$token->can(TokenAbility::ISSUE_ACCESS_TOKEN->value) || $token->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'refresh_token' => ['The refresh token is invalid or expired.'],
                ]);
            }

            $user = $token->tokenable;
            $token->delete();

            return $this->createTokens($user);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    protected function createTokens(User $user)
    {
        $accessTokenExpiresAt = Carbon::now()->addHours(config('sanctum.ac_expiration'));
        $refreshTokenExpiresAt = Carbon::now()->addHours(config('sanctum.rt_expiration'));

        $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], $accessTokenExpiresAt)->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], $refreshTokenExpiresAt)->plainTextToken;
        $secureApiUser = SecureApi::getUser($user->secure_api_id, $user->email);

        $isAdvertiser = $user->hasRole(RolesEnum::ADVERTISER->value, 'api');
        $isPublisher = $user->hasRole(RolesEnum::PUBLISHER->value, 'api');

        // Initialize billing summary
        $billingSummary = [];
        $latestPayments = [];
        $totalEarnings= 0;
        $mappings = null;

        if ($isAdvertiser) {
            $campaigns = $user->campaigns()->with(['mappings.pauseHistories', 'mappings.publisherAsset'])->get();
            $totalBillTillNow = 0;
            $totalPaid = 0;

            foreach ($campaigns as $campaign) {
                $billTillNow = $this->billingService->calculateCampaignBillTillNow($campaign);

                $paidAmount = CampaignPayment::where('campaign_id', $campaign->id)
                    ->where('advertiser_id', $user->id)
                    ->sum('amount');

                $totalBillTillNow += $billTillNow;
                $totalPaid += $paidAmount;
            }

            $billingSummary = [
                'campaign_count' => count($campaigns),
                'bill_till_now' => round($totalBillTillNow, 2),
                'paid' => round($totalPaid, 2),
                'due' => round(max($totalBillTillNow - $totalPaid, 0), 2),
            ];

            $payments = CampaignPayment::where('advertiser_id', $user->id)
                ->with('campaign')
                ->latest('payment_date')
                ->take(10)
                ->get();

            $latestPayments = $payments->map(function ($payment) {
                $cardLast4 = Str::afterLast($payment->reference, 'ending in ') ?: '****';

                return [
                    'title' => $payment->campaign
                        ? 'Payment for Ad Campaign #' . $payment->campaign->id
                        : 'Payment for Boosted Post',
                    'amount' => (float) $payment->amount,
                    'payment_date' => $payment->payment_date->format('F j, Y'),
                    'payment_method' => 'Billed to ' . ucfirst($payment->payment_method) . ' ending in ' . $cardLast4,
                    'status' => 'Completed', // or logic based if needed
                ];
            });
        }

        if ($isPublisher) {
            $mappings = CampaignMapping::with(['campaign', 'pauseHistories', 'publisherAsset'])
                ->where('publisher_id', $user->id)
                ->get();

            $totalEarnings = $mappings->sum(function ($mapping) {
                return $this->billingService->calculateCampaignMappingBill($mapping);
            });

        }

        return $this->successResponse('Logged in successfully.', [
            'id' => $user->id,
            'companyKey' => $user->secure_api_id,
            'companyName' => $secureApiUser['businessName'] ?? null,
            'logo' => $secureApiUser['businessInfo']['logoUrl'] ?? null,
            'website' => $secureApiUser['businessInfo']['websiteUrl'] ?? null,
            'address' => $secureApiUser['businessInfo']['address'] ?? null,
            'email' => $user->email,
            'phone' =>  $secureApiUser['contactInfo']['phone'] ?? null,
            'name' => $secureApiUser['contactInfo']['name'] ?? null,
            'access_token' => $accessToken,
            'access_token_expires_at' => $accessTokenExpiresAt,
            'refresh_token' => $refreshToken,
            'refresh_token_expires_at' => $refreshTokenExpiresAt,
            'token_type' => 'Bearer',
            'isAdvertiser' => $user->hasRole(RolesEnum::ADVERTISER->value,'api'),
            'isPublisher' => $user->hasRole(RolesEnum::PUBLISHER->value,'api'),
            'billing_summary' => $billingSummary,
            'recent_payments' => $latestPayments,
            'total_earnings' => $totalEarnings,
            'earnings' => $mappings ? $mappings->map(function ($mapping) {
                $campaign = $mapping->campaign;
                return [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->campaign_name ?? 'Untitled Campaign',
                    'total_earning' => round($this->billingService->calculateCampaignMappingBill($mapping), 2),
                    'asset_name' => $mapping->publisherAsset->name ?? null,
                    'pause_count' => $mapping->pauseHistories->count(),
                ];
            }) : [],
        ]);
    }
}
