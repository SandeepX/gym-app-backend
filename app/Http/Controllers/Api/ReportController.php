<?php

namespace App\Http\Controllers\Api;

use App\Services\ReportService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController
{
    use ApiResponseTrait;

    public function __construct(
        private ReportService $reportService
    ) {}

    public function expiringSubscriptions(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $data = $this->reportService->expiringSubscriptions(
            fromDate: $request->input('from_date', now()->toDateString()),
            toDate  : $request->input('to_date', now()->addDays(7)->toDateString()),
        );

        return $this->success($data, 'Expiring subscriptions report retrieved successfully.');
    }

    public function expiredSubscriptions(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $data = $this->reportService->expiredSubscriptions(
            fromDate: $request->from_date,
            toDate  : $request->to_date,
        );

        return $this->success($data, 'Expired subscriptions report retrieved successfully.');
    }

    public function inactiveMembers(Request $request): JsonResponse
    {
        $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $data = $this->reportService->inactiveMembers(
            days: $request->input('days', 30),
        );

        return $this->success($data, 'Inactive members report retrieved successfully.');
    }

    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $data = $this->reportService->revenue(
            fromDate: $request->input('from_date', now()->startOfMonth()->toDateString()),
            toDate  : $request->input('to_date', now()->toDateString()),
        );

        return $this->success($data, 'Revenue report retrieved successfully.');
    }

    public function attendance(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $data = $this->reportService->attendance(
            fromDate: $request->input('from_date', now()->startOfMonth()->toDateString()),
            toDate  : $request->input('to_date', now()->toDateString()),
        );

        return $this->success($data, 'Attendance report retrieved successfully.');
    }
}
