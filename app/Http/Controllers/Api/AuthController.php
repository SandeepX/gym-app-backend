<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\NotificationServiceInterface;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AuthController
{
    use ApiResponseTrait;

    public function __construct(private readonly NotificationServiceInterface $notificationService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $validatedData['password'] = Hash::make($request->password);

            $user = User::create($validatedData);

            $user->assignRole('member');

            $token = $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken;

            $this->notificationService->sendWelcome($user);

            return $this->success([
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => now()->addDays(30)->toIso8601String(),
            ], 'User registered successfully.', ResponseAlias::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Login and issue token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error(
                'Invalid credentials. Please try again.',
                ResponseAlias::HTTP_UNAUTHORIZED
            );
        }

        if (! $user->is_active) {
            return $this->error(
                'Your account has been deactivated. Please contact support.',
                ResponseAlias::HTTP_UNAUTHORIZED
            );
        }

        if (! $request->boolean('remember')) {
            $user->tokens()->delete();
        }

        $expiry = $request->boolean('remember')
            ? now()->addDays(90)
            : now()->addDays(1);

        $token = $user->createToken('auth_token', ['*'], $expiry)->plainTextToken;

        return $this->success([
            'access_token' => $token,
            'token' => 'Bearer',
            'expires_in' => $expiry->toIso8601String(),
        ], 'Login Success', ResponseAlias::HTTP_OK);
    }

    /**
     * Logout (revoke current token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success([], 'Logged out successfully.', Response::HTTP_OK);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->success([], 'Logged out from all devices.', Response::HTTP_OK);
    }
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->user()->currentAccessToken()->delete();

        $token = $user->createToken('auth_token', ['*'], now()->addDays(1))->plainTextToken;

        return $this->success([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => now()->addDays(1)->toIso8601String(),
        ]);
    }
}
