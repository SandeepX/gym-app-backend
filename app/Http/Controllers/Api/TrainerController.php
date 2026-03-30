<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\TrainerResource;
use App\Models\Member;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TrainerController
{
    use ApiResponseTrait;

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
            ->paginate($request->input('per_page', 15));

        return $this->success(TrainerResource::collection($trainers), 'Trainers retrieved successfully.');
    }

    public function store(RegisterRequest $request): JsonResponse
    {
        $request->validated();

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

        return $this->success(new TrainerResource($trainer->load('assignedMembers')), 'Trainer created successfully.',
            Response::HTTP_CREATED
        );
    }

    public function show($trainerId): JsonResponse
    {
        $trainer = User::role('trainer')->with([
            'assignedMembers' => fn ($q) => $q->with('user', 'activeSubscription.plan'),
        ])->find($trainerId);

        if (! $trainer) {
            return $this->error('Trainer Detail not found', Response::HTTP_NOT_FOUND);
        }

        return $this->success(new TrainerResource($trainer), 'Trainer retrieved successfully.');
    }

    public function destroy($userId): JsonResponse
    {
        $user = User::find($userId);

        if (! $user || ! $user->hasRole('trainer')) {
            return $this->error('User not found.', Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($user) {
            $user->assignedMembers()->detach();
            $user->update(['is_active' => false]);
            $user->removeRole('trainer');
            $user->delete();
        });

        return $this->success([], message: 'Trainer deleted successfully.');
    }

    public function assignMember(Request $request, $userId): JsonResponse
    {
        try {
            $request->validate([
                'member_id' => ['required', 'exists:members,id'],
            ]);

            $user = User::find($userId);

            if (! $user || ! $user->hasRole('trainer')) {
                throw new \RuntimeException('User not found.', Response::HTTP_NOT_FOUND);
            }

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
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function removeMember(Request $request, $userId): JsonResponse
    {
        $request->validate([
            'member_id' => ['required', 'exists:members,id'],
        ]);

        $user = User::find($userId);

        if (! $user || ! $user->hasRole('trainer')) {
            throw new \RuntimeException('User not found.', Response::HTTP_NOT_FOUND);
        }

        $user->assignedMembers()->detach($request->member_id);

        return $this->success([
            'trainer' => $user->name,
            'total_assigned' => $user->assignedMembers()->count(),
        ], 'Member removed from trainer successfully.');
    }

    public function myMembers(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('trainer')) {
            return $this->error('You are not a trainer.', 403);
        }

        $members = $user->assignedMembers()
            ->with(['user', 'activeSubscription.plan'])
            ->paginate($request->input('per_page', 15));

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
}
