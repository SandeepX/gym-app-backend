<?php

namespace App\Http\Controllers\Api;

use App\Enums\MemberStatusEnum;
use App\Http\Requests\MemberRequest;
use App\Http\Requests\MemberTrainerRequest;
use App\Http\Resources\MemberPlanDetailResource;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use App\Models\User;
use App\Services\MemberService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberController
{
    use ApiResponseTrait;

    public function __construct(public MemberService $memberService) {}

    public function index(Request $request): JsonResponse
    {
        $members = Member::with([
            'user',
            'activeSubscription.plan',
        ])
            ->filter($request->only(['status', 'gender', 'search']))
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
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password ?? Str::random(12)),
                'phone' => $request->phone,
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
        try {
            $member = $this->memberService->getMemberDetailById($memberId, [
                'user',
                'activeSubscription.plan',
                'subscriptions.plan',
                'payments.subscription.plan',
                'trainers',
                'bodyMeasurements',
                'workoutPlans.exercises',
                'attendances' => fn ($q) => $q->limit(10),
            ]);

            return $this->success(new MemberResource($member), 'Member retrieved successfully.');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }

    }

    public function destroy($memberId): JsonResponse
    {
        try {
            $member = $this->memberService->getMemberDetailById($memberId);

            DB::transaction(function () use ($member) {
                $member->trainers()->detach();

                $member->user->update(['is_active' => false]);

                $member->update([
                    'status' => MemberStatusEnum::Inactive,
                ]);

                $member->delete();
            });

            return $this->success([], message: 'Member deleted successfully.');

        } catch (Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function update(MemberRequest $request, $memberId): JsonResponse
    {
        $member = $this->memberService->getMemberDetailById($memberId);

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

            return $this->success(new MemberResource($member->fresh()?->load(['user', 'activeSubscription.plan'])),
                'Member updated successfully.'
            );
        });
    }

    /**
     * Assign a trainer to a member.
     */
    public function assignTrainer(MemberTrainerRequest $request, $memberId): JsonResponse
    {
        $member = $this->memberService->getMemberDetailById($memberId, ['trainers']);

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
    public function removeTrainer(MemberTrainerRequest $request, $memberId): JsonResponse
    {
        $member = $this->memberService->getMemberDetailById($memberId, ['trainers']);

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
        COUNT(*)                                                                               AS total,
        COUNT(*) FILTER (WHERE status = ?)                                                    AS active,
        COUNT(*) FILTER (WHERE status = ?)                                                    AS inactive,
        COUNT(*) FILTER (WHERE status = ?)                                                    AS suspended,
        COUNT(*) FILTER (WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW())) AS new_this_month
    ", [
            MemberStatusEnum::Active->value,
            MemberStatusEnum::Inactive->value,
            MemberStatusEnum::Suspended->value,
        ])->first();

        return $this->success((array) $stats, 'Member stats retrieved successfully.');
    }

    public function memberWorkoutPlansDetails($memberId): JsonResponse
    {
        $memberWorkoutPlan = $this->memberService->getMemberDetailById($memberId, ['workoutPlans.exercises']);

        return $this->success(new MemberPlanDetailResource($memberWorkoutPlan), 'Member workout plans retrieved successfully.');
    }
}
