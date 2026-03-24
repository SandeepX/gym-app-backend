<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatusEnum;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\NotificationServiceInterface;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class PaymentController
{
    use ApiResponseTrait;

    public function __construct(
        private NotificationServiceInterface $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $payments = Payment::with(['member.user', 'subscription.plan', 'collectedBy'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->member_id, fn ($q) => $q->where('member_id', $request->member_id))
            ->when($request->from_date, fn ($q) => $q->whereDate('paid_at', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->whereDate('paid_at', '<=', $request->to_date))
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->success(
            PaymentResource::collection($payments),
            'Payments retrieved successfully.'
        );
    }

    public function store(PaymentRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['invoice_number'] = Payment::generateSequenceNumber('INV', 'invoice_number');
        $validatedData['paid_at'] = $request->paid_at ?? now();
        $validatedData['collected_by'] = $request->user()->id;

        $payment = Payment::create($validatedData);

        $this->notificationService->sendPaymentReceived($payment);

        return $this->success(
            PaymentResource::make($payment->load(['member.user', 'subscription.plan'])),
            'Payment recorded successfully.',
            201
        );
    }

    public function show(Payment $payment): JsonResponse
    {
        return $this->success(
            PaymentResource::make($payment->load(['member.user', 'subscription.plan', 'collectedBy'])),
            'Payment retrieved successfully.'
        );
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        $request->validate([
            'status' => ['required', new Enum(PaymentStatusEnum::class)],
            'notes' => ['nullable', 'string'],
        ]);

        $payment->update($request->only(['status', 'notes']));

        return $this->success(
            PaymentResource::make($payment->fresh()),
            'Payment updated successfully.'
        );
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();

        return $this->success([], message: 'Payment deleted successfully.');
    }

    public function stats(): JsonResponse
    {
        $stats = Payment::toBase()->selectRaw("
            COUNT(*)                                                         AS total_transactions,
            COALESCE(SUM(amount) FILTER (WHERE status = 'paid'), 0)         AS total_revenue,
            COALESCE(SUM(amount) FILTER (WHERE status = 'pending'), 0)      AS pending_amount,
            COALESCE(SUM(amount) FILTER (WHERE status = 'refunded'), 0)     AS refunded_amount,
            COUNT(*) FILTER (WHERE DATE_TRUNC('month', paid_at) = DATE_TRUNC('month', NOW())) AS this_month_transactions,
            COALESCE(SUM(amount) FILTER (WHERE DATE_TRUNC('month', paid_at) = DATE_TRUNC('month', NOW()) AND status = 'paid'), 0) AS this_month_revenue
        ")->first();

        return $this->success((array) $stats, 'Payment stats retrieved successfully.');
    }
}
