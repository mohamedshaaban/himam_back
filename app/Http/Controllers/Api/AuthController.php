<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'locale' => ['nullable', Rule::in(array_keys(config('himam.locales')))],
        ]);

        $user = User::create([
            ...$data,
            'locale' => $data['locale'] ?? app()->getLocale(),
            'role' => 'student',
        ]);

        // Every category starts opted in; the account screen is where a reader
        // turns things off.
        foreach (NotificationPreference::CATEGORIES as $category) {
            $user->notificationPreferences()->create(['category' => $category, 'enabled' => true]);
        }

        return response()->json([
            'token' => $user->createToken('himam')->plainTextToken,
            'user' => new UserResource($user->load('level')),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            // One message for both cases so the endpoint can't be used to
            // discover which addresses are registered.
            throw ValidationException::withMessages([
                'email' => [__('These credentials do not match our records.')],
            ]);
        }

        return response()->json([
            'token' => $user->createToken('himam')->plainTextToken,
            'user' => new UserResource($user->load('level')),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource(
            $request->user()->loadCount(['badges', 'certificates'])->load('level')
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => __('Signed out.')]);
    }
}
