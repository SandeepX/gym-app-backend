<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Member;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $attendance = Attendance::with(['member.user', 'checkedInBy'])
            ->when($request->member_id, fn ($q) => $q->where('member_id', $request->member_id))
            ->when($request->date, fn ($q) => $q->whereDate('check_in', $request->date))
            ->latest('check_in')
            ->paginate($request->get('per_page', 15));

        return $this->success(
            AttendanceResource::collection($attendance),
            'Attendance retrieved successfully.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        return $this->checkIn($request);
    }

    public function show(Attendance $attendance): JsonResponse
    {
        return $this->success(
            AttendanceResource::make($attendance->load(['member.user', 'checkedInBy'])),
            'Attendance retrieved successfully.'
        );
    }

    public function update(Request $request, Attendance $attendance): JsonResponse
    {
        $request->validate(['notes' => ['nullable', 'string']]);
        $attendance->update($request->only(['notes']));

        return $this->success(AttendanceResource::make($attendance->fresh()), 'Attendance updated.');
    }

    public function destroy(Attendance $attendance): JsonResponse
    {
        $attendance->delete();

        return $this->success(message: 'Attendance deleted successfully.');
    }

    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $existing = Attendance::where('member_id', $request->member_id)
            ->whereNull('check_out')
            ->whereDate('check_in', today())
            ->first();

        if ($existing) {
            return $this->error('Member is already checked in.', 422);
        }

        $attendance = Attendance::create([
            'member_id' => $request->member_id,
            'checked_in_by' => $request->user()->id,
            'check_in' => now(),
            'notes' => $request->notes,
        ]);

        return $this->success(
            AttendanceResource::make($attendance->load(['member.user'])),
            'Member checked in successfully.',
            201
        );
    }

    public function checkOut(Request $request, Attendance $attendance): JsonResponse
    {
        if ($attendance->check_out) {
            return $this->error('Member already checked out.', 422);
        }

        $attendance->update(['check_out' => now()]);

        return $this->success(
            AttendanceResource::make($attendance->fresh()->load(['member.user'])),
            "Member checked out. Duration: {$attendance->fresh()->durationMinutes()} minutes."
        );
    }

    public function today(Request $request): JsonResponse
    {
        $attendance = Attendance::with(['member.user'])
            ->whereDate('check_in', today())
            ->latest('check_in')
            ->get();

        return $this->success(
            AttendanceResource::collection($attendance),
            "Today's attendance: {$attendance->count()} members."
        );
    }

    public function memberHistory(Request $request, Member $member): JsonResponse
    {
        $attendance = Attendance::where('member_id', $member->id)
            ->latest('check_in')
            ->paginate($request->get('per_page', 15));

        return $this->success(
            AttendanceResource::collection($attendance),
            'Member attendance history retrieved.'
        );
    }
}
