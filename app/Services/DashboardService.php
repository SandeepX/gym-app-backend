<?php

namespace App\Services;

use App\Enums\MemberStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;

class DashboardService
{
    public function memberStats(): array
    {
        return Member::query()
            ->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as suspended,
            SUM(CASE
                WHEN DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW())
                THEN 1 ELSE 0
            END) as new_this_month
        ", [
                MemberStatusEnum::Active->value,
                MemberStatusEnum::Inactive->value,
                MemberStatusEnum::Suspended->value,
            ])
            ->first()
            ?->toArray();
    }

    public function subscriptionStats(): array
    {
        $stats = Subscription::query()
            ->selectRaw("
            COUNT(*) FILTER (WHERE status = ?) AS active,
            COUNT(*) FILTER (WHERE status = ?) AS expired,
            COUNT(*) FILTER (WHERE status = ?) AS frozen,
            COUNT(*) FILTER (
                WHERE status = ?
                AND end_date BETWEEN NOW() AND NOW() + INTERVAL '7 days'
            ) AS expiring_in_7_days,
            COUNT(*) FILTER (
                WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW())
            ) AS new_this_month
        ", [
                SubscriptionStatusEnum::Active->value,
                SubscriptionStatusEnum::Expired->value,
                SubscriptionStatusEnum::Frozen->value,
                SubscriptionStatusEnum::Active->value,
            ])
            ->first();

        return [
            'active' => (int) ($stats->active ?? 0),
            'expired' => (int) ($stats->expired ?? 0),
            'frozen' => (int) ($stats->frozen ?? 0),
            'expiring_in_7_days' => (int) ($stats->expiring_in_7_days ?? 0),
            'new_this_month' => (int) ($stats->new_this_month ?? 0),
        ];
    }

    public function revenueStats(): array
    {
        $stats = Payment::query()
            ->selectRaw("
            COALESCE(SUM(amount) FILTER (WHERE status = ?), 0) AS total_revenue,
            COALESCE(SUM(amount) FILTER (
                WHERE status = ?
                AND DATE_TRUNC('month', paid_at) = DATE_TRUNC('month', NOW())
            ), 0) AS this_month,
            COALESCE(SUM(amount) FILTER (
                WHERE status = ?
                AND DATE_TRUNC('month', paid_at) = DATE_TRUNC('month', NOW() - INTERVAL '1 month')
            ), 0) AS last_month,
            COUNT(*) FILTER (WHERE status = ?) AS pending_payments
        ", [
                PaymentStatusEnum::Paid->value,
                PaymentStatusEnum::Paid->value,
                PaymentStatusEnum::Paid->value,
                PaymentStatusEnum::Pending->value,
            ])
            ->first()
            ?->toArray();

        $thisMonth = (float) $stats['this_month'];
        $lastMonth = (float) $stats['last_month'];

        $stats['growth'] = $lastMonth > 0
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2)
            : 100;

        return $stats;
    }

    public function attendanceStats(): array
    {
        $stats = Attendance::toBase()->selectRaw("
            COUNT(*) FILTER (WHERE DATE(check_in) = CURRENT_DATE)                               AS today,
            COUNT(*) FILTER (WHERE DATE_TRUNC('week', check_in) = DATE_TRUNC('week', NOW()))    AS this_week,
            COUNT(*) FILTER (WHERE DATE_TRUNC('month', check_in) = DATE_TRUNC('month', NOW()))  AS this_month
        ")->first();

        return (array) $stats;
    }
}
