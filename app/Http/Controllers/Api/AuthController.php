<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AuthController
{
    use ApiResponseTrait;

    public function register(RegisterRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $validatedData['password'] = Hash::make($request->password);

        $user = User::create($validatedData);

        $user->assignRole('member');

        $token = $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => now()->addDays(30)->toIso8601String(),
        ], 'User registered successfully.', Response::HTTP_CREATED);
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

    public function profile(Request $request): JsonResponse
    {
        return $this->success([new UserResource($request->user()->load(['roles', 'permissions']))]);
    }

    public function updateProfile(UpdateProfileRequest $request, $userId): JsonResponse
    {
        $validatedData = $request->validated();

        $user = User::find($userId);

        if (! $user) {
            $this->error('User Not found', 404);
        }

        $user->update($validatedData);

        return $this->success([new UserResource($user->fresh()?->load(['roles']))],
            'Update successful');
    }

    public function changePassword(ChangePasswordRequest $request, $userId): JsonResponse
    {
        $user = User::find($userId);

        if (! $user) {
            $this->error('User not found', 404);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            $this->error('Current password is incorrect.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return $this->success([], 'Password changed successfully.');
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
