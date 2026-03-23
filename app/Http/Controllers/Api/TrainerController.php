<?php

namespace App\Http\Controllers\Api;

use App\Models\Member;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TrainerController
{
    use ApiResponseTrait;

    /**
     * List all trainers.
     */
    public function index(Request $request): JsonResponse
    {
        $trainers = User::role('trainer')
            ->with('assignedMembers.user')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
            )
            ->when(isset($request->is_active), fn ($q) => $q->where('is_active', $request->boolean('is_active'))
            )
            ->withCount('assignedMembers')
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success(
            $trainers->through(fn ($trainer) => $this->formatTrainer($trainer)),
            'Trainers retrieved successfully.'
        );
    }

    /**
     * Create a new trainer (creates User + assigns trainer role).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $trainer = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'is_active' => true,
            ]);

            $user->assignRole('trainer');

            return $user;
        });

        return $this->success(
            $this->formatTrainer($trainer->load('assignedMembers')),
            'Trainer created successfully.',
            201
        );
    }

    /**
     * Show single trainer with assigned members.
     */
    public function show(User $user): JsonResponse
    {
        if (! $user->hasRole('trainer')) {
            return $this->error('Trainer not found.', 404);
        }

        $user->load([
            'assignedMembers' => fn ($q) => $q->with('user', 'activeSubscription.plan'),
        ])->loadCount('assignedMembers');

        return $this->success(
            $this->formatTrainer($user),
            'Trainer retrieved successfully.'
        );
    }

    /**
     * Update trainer info.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if (! $user->hasRole('trainer')) {
            return $this->error('Trainer not found.', 404);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', "unique:users,phone,{$user->id}"],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user->update($request->only(['name', 'phone', 'is_active']));

        return $this->success(
            $this->formatTrainer($user->fresh()->load('assignedMembers')),
            'Trainer updated successfully.'
        );
    }

    /**
     * Delete trainer.
     */
    public function destroy(User $user): JsonResponse
    {
        if (! $user->hasRole('trainer')) {
            return $this->error('Trainer not found.', 404);
        }

        DB::transaction(function () use ($user) {
            $user->assignedMembers()->detach();
            $user->update(['is_active' => false]);
            $user->removeRole('trainer');
            $user->delete();
        });

        return $this->success(message: 'Trainer deleted successfully.');
    }

    /**
     * Assign member to trainer.
     */
    public function assignMember(Request $request, User $user): JsonResponse
    {
        if (! $user->hasRole('trainer')) {
            return $this->error('Trainer not found.', 404);
        }

        $request->validate([
            'member_id' => ['required', 'exists:members,id'],
        ]);

        $member = Member::findOrFail($request->member_id);

        if ($user->assignedMembers()->where('member_id', $member->id)->exists()) {
            return $this->error('Member already assigned to this trainer.', 422);
        }

        $user->assignedMembers()->attach($member->id);

        return $this->success([
            'trainer' => $user->name,
            'member' => $member->user->name,
            'total_assigned' => $user->assignedMembers()->count(),
        ], 'Member assigned to trainer successfully.');
    }

    /**
     * Remove member from trainer.
     */
    public function removeMember(Request $request, User $user): JsonResponse
    {
        if (! $user->hasRole('trainer')) {
            return $this->error('Trainer not found.', 404);
        }

        $request->validate([
            'member_id' => ['required', 'exists:members,id'],
        ]);

        $user->assignedMembers()->detach($request->member_id);

        return $this->success([
            'trainer' => $user->name,
            'total_assigned' => $user->assignedMembers()->count(),
        ], 'Member removed from trainer successfully.');
    }

    /**
     * Get members assigned to a trainer.
     */
    public function myMembers(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('trainer')) {
            return $this->error('You are not a trainer.', 403);
        }

        $members = $user->assignedMembers()
            ->with(['user', 'activeSubscription.plan'])
            ->paginate($request->get('per_page', 15));

        return $this->success(
            $members->through(fn ($member) => [
                'id' => $member->id,
                'membership_number' => $member->membership_number,
                'name' => $member->user->name,
                'email' => $member->user->email,
                'status' => $member->status->value,
                'active_subscription' => $member->activeSubscription ? [
                    'plan' => $member->activeSubscription->plan?->name,
                    'end_date' => $member->activeSubscription->end_date?->format('Y-m-d'),
                    'days_remaining' => $member->activeSubscription->daysRemaining(),
                ] : null,
            ]),
            'Assigned members retrieved successfully.'
        );
    }

    /**
     * Format trainer response.
     */
    private function formatTrainer(User $trainer): array
    {
        return [
            'id' => $trainer->id,
            'name' => $trainer->name,
            'email' => $trainer->email,
            'phone' => $trainer->phone,
            'is_active' => $trainer->is_active,
            'assigned_members_count' => $trainer->assigned_members_count
                ?? $trainer->assignedMembers->count(),
            'assigned_members' => $trainer->relationLoaded('assignedMembers')
                ? $trainer->assignedMembers->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->user?->name,
                    'membership_number' => $m->membership_number,
                    'status' => $m->status->value,
                ])
                : [],
            'created_at' => $trainer->created_at,
        ];
    }
}
