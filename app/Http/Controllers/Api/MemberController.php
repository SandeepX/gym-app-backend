<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\AssignTrainerRequest;
use App\Http\Requests\MemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $members = Member::with(['user', 'activeSubscription.plan'])
            ->when($request->search, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
            )
            )
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->gender, fn ($q) => $q->where('gender', $request->gender))
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->success(
            MemberResource::collection($members),
            'Members retrieved successfully.'
        );
    }

    public function store(MemberRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        return DB::transaction(function () use ($request, $validatedData) {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password ?? Str::random(12)),
                'phone'     => $request->phone,
                'is_active' => true,
            ]);

            $user->assignRole('member');

            unset(
                $validatedData['name'],
                $validatedData['email'],
                $validatedData['password'],
                $validatedData['phone'],
            );

            $validatedData['membership_number'] = Member::generateSequenceNumber('GYM');

            $member = $user->member()->create($validatedData);

            return $this->success(
                new MemberResource($member->load('user')),
                'Member created successfully.',
                201
            );
        });
    }

    public function show($memberId): JsonResponse
    {
        $member = Member::with([
            'user',
            'activeSubscription.plan',
            'subscriptions.plan',
            'payments.subscription.plan',
            'trainers',
            'attendances' => fn ($q) => $q->limit(10),
        ])->find($memberId);

        if (! $member) {
            return $this->error('Member not found.', 404);
        }

        return $this->success(
            MemberResource::make($member),
            'Member retrieved successfully.'
        );
    }

    public function update(MemberRequest $request, Member $member): JsonResponse
    {
        return DB::transaction(function () use ($request, $member) {
            $member->user->update($request->only(['name', 'phone']));

            $member->update($request->only([
                'date_of_birth',
                'gender',
                'address',
                'emergency_contact_name',
                'emergency_contact_phone',
                'health_notes',
                'status',
            ]));

            return $this->success(
                MemberResource::make(
                    $member->fresh()->load(['user', 'activeSubscription.plan'])
                ),
                'Member updated successfully.'
            );
        });
    }

    public function destroy($memberId): JsonResponse
    {
        $member = Member::find($memberId);

        if (! $member) {
            return $this->error('Member not found.', 404);
        }

        DB::transaction(function () use ($member) {
            $member->trainers()->detach();
            $member->user->update(['is_active' => false]);
            $member->update(['status' => 'inactive']);
            $member->delete();
        });

        return $this->success(message: 'Member deleted successfully.');
    }

    /**
     * Assign a trainer to a member.
     */
    public function assignTrainer(AssignTrainerRequest $request, $memberId): JsonResponse
    {
        $member = User::findOrFail($memberId);

        $trainer = User::find($request->trainer_id);

        if ($member->trainers()->where('user_id', $trainer->id)->exists()) {
            return $this->error('This trainer is already assigned to the member.', 422);
        }

        $member->trainers()->attach($trainer->id);

        return $this->success([
            'member' => $member->user->name,
            'trainer' => $trainer->name,
            'total_trainers' => $member->trainers()->count(),
        ], 'Trainer assigned to member successfully.');
    }

    /**
     * Remove a trainer from a member.
     */
    public function removeTrainer(Request $request, $memberId): JsonResponse
    {
        $request->validate([
            'trainer_id' => ['required', 'exists:users,id'],
        ]);

        $member = User::findOrFail($memberId);

        $trainer = User::findOrFail($request->trainer_id);

        if (! $member->trainers()->where('user_id', $trainer->id)->exists()) {
            return $this->error('This trainer is not assigned to the member.', 422);
        }

        $member->trainers()->detach($trainer->id);

        return $this->success([
            'member' => $member->user->name,
            'trainer' => $trainer->name,
            'total_trainers' => $member->trainers()->count(),
        ], 'Trainer removed from member successfully.');
    }

    public function stats(): JsonResponse
    {
        $stats = Member::toBase()->selectRaw("
            COUNT(*)                                                                          AS total,
            COUNT(*) FILTER (WHERE status = 'active')                                        AS active,
            COUNT(*) FILTER (WHERE status = 'inactive')                                      AS inactive,
            COUNT(*) FILTER (WHERE status = 'suspended')                                     AS suspended,
            COUNT(*) FILTER (WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW())) AS new_this_month
        ")->first();

        return $this->success((array) $stats, 'Member stats retrieved successfully.');
    }
}
