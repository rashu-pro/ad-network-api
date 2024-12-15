<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\TokenAbility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AdminLoginController extends Controller
{
    use ApiResponse;
    public function login(AdminLoginRequest $request)
    {
        try {
            $request->authenticate();
            $user = $request->user;
            return $this->createTokens($user);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

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
        $accessTokenExpiresAt = Carbon::now()->addMinutes(config('sanctum.ac_expiration'));
        $refreshTokenExpiresAt = Carbon::now()->addMinutes(config('sanctum.rt_expiration'));

        $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], $accessTokenExpiresAt)->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], $refreshTokenExpiresAt)->plainTextToken;

        return $this->successResponse('Logged in successfully.', [
            'access_token' => $accessToken,
            'access_token_expires_at' => $accessTokenExpiresAt,
            'refresh_token' => $refreshToken,
            'refresh_token_expires_at' => $refreshTokenExpiresAt,
            'token_type' => 'Bearer',
        ]);
    }
}
