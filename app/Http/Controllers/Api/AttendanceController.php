<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\AttendanceCheckInRequest;
use App\Http\Requests\AttendanceCheckoutRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\MemberService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AttendanceController
{
    use ApiResponseTrait;

    public function __construct(public MemberService $memberService) {}

    public function index(Request $request): JsonResponse
    {
        $attendance = Attendance::with(['member.user', 'checkedInBy'])
            ->filter($request->only(['member_id', 'date', 'from_date', 'to_date']))
            ->latest('check_in')
            ->paginate($request->input('per_page', 15));

        return $this->success(AttendanceResource::collection($attendance),
            'Attendance retrieved successfully.');
    }

    public function checkIn(AttendanceCheckInRequest $request): JsonResponse
    {
        $request->validated();

        $existing = Attendance::query()
            ->where('member_id', $request->member_id)
            ->whereNull('check_out')
            ->whereDate('check_in', today())
            ->first();

        if ($existing) {
            return $this->error('Member is already checked in.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $attendance = Attendance::create([
            'member_id' => $request->member_id,
            'checked_in_by' => $request->user()->id,
            'check_in' => now(),
            'notes' => $request->notes,
        ]);

        return $this->success(new AttendanceResource($attendance->load(['member.user'])),
            'Member checked in successfully.',
            ResponseAlias::HTTP_CREATED
        );
    }

    public function checkout(Request $request, $attendanceId): JsonResponse
    {
        $attendance = Attendance::find($attendanceId);
        if (! $attendance) {
            return $this->error('Attendance Detail Not Found');
        }

        if ($attendance->check_out) {
            return $this->error('Member already checked out.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $attendance->update(['check_out' => now()]);

        $attendance->fresh()->load(['member.user']);

        return $this->success(new AttendanceResource($attendance),
            "Member checked out. Duration: {$attendance->fresh()->durationMinutes()} minutes."
        );
    }

    public function update(AttendanceCheckoutRequest $request, $attendanceId): JsonResponse
    {
        $request->validated();

        $attendance = Attendance::find($attendanceId);

        if (! $attendance) {
            return $this->error('Attendance Detail Not Found');
        }

        $attendance->update($request->only(['notes', 'check_in', 'check_out']));

        return $this->success(new AttendanceResource($attendance->fresh()),
            'Attendance updated.');
    }

    public function memberHistory(Request $request, $memberId): JsonResponse
    {
        $member = $this->memberService->getMemberDetailById($memberId, ['attendances']);

        return $this->success(AttendanceResource::collection($member->attendances),
            'Member attendance history retrieved.'
        );
    }
}
