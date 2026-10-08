<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\User;


class ApiPasswordResetController extends Controller
{
    /**
     * Generate a password reset link and send it to the provided email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if the email exists in the database
        if (!User::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'Email does not exist',
            ], 404);
        }

        // Generate the password reset token
        $token = Password::broker()->createToken(User::where('email', $request->email)->first());

        if (!$token) {
            return response()->json([
                'message' => 'Failed to generate reset token',
            ], 500);
        }

        // Generate the reset link
        $resetLink = url("/password/reset/{$token}?email={$request->email}");

        // Define the URL for the developer's API
        $developerApiUrl = 'https://www.artisanfurniture.net/wp-json/rest-product/send-forget-password-email-laravel';

        // Send the email and link to the developer's API
        $apiResponse = Http::get($developerApiUrl, [
            'email' => $request->email,
            'reset_link' => $resetLink,
        ]);

        if ($apiResponse->successful()) {
            return response()->json([
                'message' => 'Password reset link has been sent to your email',
            ]);
        } else {
            return response()->json([
                'message' => 'Failed to send reset link to the developer API',
            ], 500);
        }
    }
}
