<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Forgotten-password flow, in two steps.
 *
 * Laravel's password broker does the work: it issues a signed token, stores its
 * hash with an expiry, and refuses a token that has been used or has aged out.
 * Both endpoints are public — a reader who cannot sign in obviously cannot
 * present a token.
 */
class PasswordResetController extends Controller
{
    /**
     * Step one: email a reset link.
     *
     * The reply is the same whether or not the address is registered. Saying
     * "no such account" here would turn this endpoint into a way to test which
     * addresses have accounts, which is worth more to an attacker than the
     * small convenience it offers a reader who mistyped their email.
     */
    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($data);

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'message' => __('A reset link was sent recently. Please wait before asking for another.'),
            ], 429);
        }

        return response()->json([
            'message' => __('If that address has an account, a reset link is on its way.'),
        ]);
    }

    /**
     * Step two: set the new password against the emailed token.
     */
    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($data, function ($user, string $password) {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            // Anyone holding a token for this account had the old password, or
            // took it. Resetting is the moment to end those sessions.
            $user->tokens()->delete();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__('This reset link is invalid or has expired. Please request a new one.')],
            ]);
        }

        return response()->json([
            'message' => __('Your password has been reset. You can sign in now.'),
        ]);
    }
}
