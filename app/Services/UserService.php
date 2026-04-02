<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class UserService
{
    public function getUserDetailById($userId, $with = [])
    {
        $user = User::with($with)->find($userId);

        if (! $user) {
            throw new RuntimeException('User Detail Not Found', Response::HTTP_NOT_FOUND);
        }

        return $user;
    }

    public function store(Request $request)
    {
        $insertData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password ?? Str::random(12)),
            'phone' => $request->phone,
            'is_active' => true,
        ];

        return User::create($insertData);
    }
}
