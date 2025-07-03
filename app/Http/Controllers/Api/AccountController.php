<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    public function deleteAccount(Request $request)
    {
        $user = Auth::guard('api')->user();

        DB::transaction(function () use ($user) {
            // Optional: log this for audit
            Log::info('User account deletion initiated', ['user_id' => $user->id, 'role' => $user->role]);

            // Clean up related data (optional):
             $user->campaigns()->delete();
             $user->assets()->delete();


            $user->delete();
        });

        return response()->json([
            'message' => 'Your account has been deleted successfully.'
        ]);
    }
}
