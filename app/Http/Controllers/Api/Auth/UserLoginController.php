<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\RolesEnum;
use App\Enums\TokenAbility;
use App\Events\AdvertiserRegistered;
use App\Facades\SecureApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdvertiserLoginRequest;
use App\Models\Advertiser;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Role;

class UserLoginController extends Controller
{
    use ApiResponse;

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

            if (!$localUser) {
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
//        $secureApiUser = SecureApi::getUser($user->secure_api_id);
        return $this->successResponse('Logged in successfully.', [
            'id' => $user->id,
            'companyKey' => $user->secure_api_id,
            'email' => $user->email,
            'name' => $user->name,
            'access_token' => $accessToken,
            'access_token_expires_at' => $accessTokenExpiresAt,
            'refresh_token' => $refreshToken,
            'refresh_token_expires_at' => $refreshTokenExpiresAt,
            'token_type' => 'Bearer',
            'isAdvertiser' => $user->hasRole(RolesEnum::ADVERTISER->value,'api'),
            'isPublisher' => $user->hasRole(RolesEnum::PUBLISHER->value,'api'),
        ]);
    }
}
