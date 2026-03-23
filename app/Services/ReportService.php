<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function expiringSubscriptions(string $fromDate, string $toDate): array
    {
        $subscriptions = Subscription::with(['member.user', 'plan'])
            ->where('status', 'active')
            ->whereBetween('end_date', [$fromDate, $toDate])
            ->orderBy('end_date')
            ->get();

        $summary = Subscription::toBase()->selectRaw("
        COUNT(*) FILTER (WHERE end_date = CURRENT_DATE)                                AS expiring_today,
        COUNT(*) FILTER (WHERE end_date BETWEEN NOW() AND NOW() + INTERVAL '3 days')  AS expiring_in_3_days,
        COUNT(*) FILTER (WHERE end_date BETWEEN NOW() AND NOW() + INTERVAL '7 days')  AS expiring_in_7_days,
        COUNT(*) FILTER (WHERE end_date BETWEEN NOW() AND NOW() + INTERVAL '30 days') AS expiring_in_30_days
    ")
            ->where('status', 'active')
            ->first();

        return [
            'summary' => (array) $summary,
            'period' => ['from' => $fromDate, 'to' => $toDate],
            'total' => $subscriptions->count(),
            'data' => $subscriptions->map(fn ($sub) => [
                'subscription_id' => $sub->id,
                'subscription_number' => $sub->subscription_number,
                'member' => [
                    'id' => $sub->member->id,
                    'name' => $sub->member->user->name,
                    'email' => $sub->member->user->email,
                    'phone' => $sub->member->user->phone,
                    'membership_number' => $sub->member->membership_number,
                ],
                'plan' => [
                    'id' => $sub->plan->id,
                    'name' => $sub->plan->name,
                    'price' => $sub->plan->price,
                ],
                'start_date' => $sub->start_date->format('Y-m-d'),
                'end_date' => $sub->end_date->format('Y-m-d'),
                'days_remaining' => $sub->daysRemaining(),
                'auto_renew' => $sub->auto_renew,
            ])->values(),
        ];
    }

    public function expiredSubscriptions(?string $fromDate, ?string $toDate): array
    {
        $subscriptions = Subscription::with(['member.user', 'plan'])
            ->where('status', 'expired')
            ->when($fromDate, fn ($q) => $q->whereDate('end_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('end_date', '<=', $toDate))
            ->orderByDesc('end_date')
            ->get();

        return [
            'period' => ['from' => $fromDate, 'to' => $toDate],
            'total' => $subscriptions->count(),
            'data' => $subscriptions->map(fn ($sub) => [
                'subscription_number' => $sub->subscription_number,
                'member' => [
                    'name' => $sub->member->user->name,
                    'email' => $sub->member->user->email,
                    'phone' => $sub->member->user->phone,
                    'membership_number' => $sub->member->membership_number,
                ],
                'plan' => $sub->plan->name,
                'expired_on' => $sub->end_date->format('Y-m-d'),
                'auto_renew' => $sub->auto_renew,
            ])->values(),
        ];
    }

    public function inactiveMembers(int $days): array
    {
        $members = Member::with(['user', 'activeSubscription.plan'])
            ->where('status', 'active')
            ->where(function ($q) use ($days) {
                $q->whereDoesntHave('attendances')
                    ->orWhereHas('attendances', fn ($q) => $q->havingRaw('MAX(check_in) < ?', [now()->subDays($days)])
                    );
            })
            ->get();

        return [
            'filter' => "No visit in last {$days} days",
            'total' => $members->count(),
            'data' => $members->map(fn ($member) => [
                'id' => $member->id,
                'membership_number' => $member->membership_number,
                'name' => $member->user->name,
                'email' => $member->user->email,
                'phone' => $member->user->phone,
                'active_plan' => $member->activeSubscription?->plan?->name ?? 'No active plan',
                'subscription_ends' => $member->activeSubscription?->end_date?->format('Y-m-d'),
                'last_visit' => $member->attendances()
                    ->latest('check_in')
                    ->value('check_in')?->format('Y-m-d') ?? 'Never',
            ])->values(),
        ];
    }

    public function revenue(string $fromDate, string $toDate): array
    {
        $payments = Payment::with(['member.user', 'subscription.plan'])
            ->whereBetween('paid_at', [$fromDate, $toDate])
            ->where('status', 'paid')
            ->get();

        $summary = Payment::toBase()->selectRaw('
        COUNT(*)                 AS total_transactions,
        COALESCE(SUM(amount), 0) AS total_revenue,
        COALESCE(AVG(amount), 0) AS average_payment,
        MAX(amount)              AS highest_payment,
        MIN(amount)              AS lowest_payment
    ')
            ->whereBetween('paid_at', [$fromDate, $toDate])
            ->where('status', 'paid')
            ->first();

        return [
            'period' => ['from' => $fromDate, 'to' => $toDate],
            'summary' => (array) $summary,
            'total' => $payments->count(),
            'data' => $payments->map(fn ($payment) => [
                'invoice_number' => $payment->invoice_number,
                'member' => $payment->member->user->name,
                'plan' => $payment->subscription?->plan?->name ?? 'N/A',
                'amount' => $payment->amount,
                'method' => $payment->payment_method->value,
                'paid_at' => $payment->paid_at->format('Y-m-d H:i'),
            ])->values(),
        ];
    }

    /**
     * Attendance report.
     */
    public function attendance(string $fromDate, string $toDate)
    {
        $daily = Attendance::selectRaw('
            DATE(check_in)                                                                   AS date,
            COUNT(*)                                                                         AS total_visits,
            COUNT(DISTINCT member_id)                                                        AS unique_members,
            ROUND(AVG(EXTRACT(EPOCH FROM (check_out - check_in)) / 60))::int                AS avg_duration_minutes
        ')
            ->whereBetween(DB::raw('DATE(check_in)'), [$fromDate, $toDate])
            ->groupByRaw('DATE(check_in)')
            ->orderByRaw('DATE(check_in)')
            ->get();

        $summary = Attendance::toBase()->selectRaw('
            COUNT(*)                                                                         AS total_visits,
            COUNT(DISTINCT member_id)                                                        AS unique_members,
            ROUND(AVG(EXTRACT(EPOCH FROM (check_out - check_in)) / 60))::int                AS avg_duration_minutes
        ')
            ->whereBetween(DB::raw('DATE(check_in)'), [$fromDate, $toDate])
            ->first();

        return [
            'period' => ['from' => $fromDate, 'to' => $toDate],
            'summary' => (array) $summary,
            'daily_chart' => $daily,
        ];
    }
}
