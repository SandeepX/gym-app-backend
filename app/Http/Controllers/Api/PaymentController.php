<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\NotificationServiceInterface;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly NotificationServiceInterface $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $payments = Payment::with(['member.user', 'subscription.plan', 'collectedBy'])
            ->applyFilters($request)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->success(PaymentResource::collection($payments),
            'Payments retrieved successfully.'
        );
    }

    public function store(PaymentRequest $request): JsonResponse
    {
        $payment = Payment::create([
            ...$request->validated(),
            'invoice_number' => Payment::generateSequenceNumber('INV', 'invoice_number'),
            'paid_at' => $request->paid_at ?? now(),
            'collected_by' => $request->user()->id,
        ]);

        $this->notificationService->sendPaymentReceived($payment);

        $payment->load(['member.user', 'subscription.plan']);

        return $this->success(new PaymentResource($payment), 'Payment recorded successfully.',
            Response::HTTP_CREATED
        );
    }

    public function show($paymentId): JsonResponse
    {
        $payment = Payment::with(['member.user', 'subscription.plan', 'collectedBy'])->find($paymentId);

        if (! $payment) {
            return $this->error('Payment not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->success(new PaymentResource($payment),
            'Payment retrieved successfully.'
        );
    }

    public function update(UpdatePaymentRequest $request, $paymentId): JsonResponse
    {
        try {
            $request->validated();

            $payment = Payment::find($paymentId);
            if (! $payment) {
                return $this->error('Payment not found.', Response::HTTP_NOT_FOUND);
            }

            $payment->update($request->only(['status', 'notes']));

            return $this->success(new PaymentResource($payment->fresh()), 'Payment updated successfully.');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($paymentId): JsonResponse
    {
        $payment = Payment::find($paymentId);

        if (! $payment) {
            return $this->error('Payment not found.', Response::HTTP_NOT_FOUND);
        }

        $payment->delete();

        return $this->success([], message: 'Payment deleted successfully.');
    }
}
