<?php

namespace App\Http\Services;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;

class DashboardService
{
    public function memberStats(): array
    {
        $stats = Member::toBase()->selectRaw("
            COUNT(*)                                                                              AS total,
            COUNT(*) FILTER (WHERE status = 'active')                                            AS active,
            COUNT(*) FILTER (WHERE status = 'inactive')                                          AS inactive,
            COUNT(*) FILTER (WHERE status = 'suspended')                                         AS suspended,
            COUNT(*) FILTER (WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW())) AS new_this_month
        ")->first();

        return (array) $stats;
    }

    public function subscriptionStats(): array
    {
        $stats = Subscription::toBase()->selectRaw("
            COUNT(*) FILTER (WHERE status = 'active')                                                           AS active,
            COUNT(*) FILTER (WHERE status = 'expired')                                                          AS expired,
            COUNT(*) FILTER (WHERE status = 'frozen')                                                           AS frozen,
            COUNT(*) FILTER (WHERE end_date BETWEEN NOW() AND NOW() + INTERVAL '7 days' AND status = 'active') AS expiring_in_7_days,
            COUNT(*) FILTER (WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW()))                AS new_this_month
        ")->first();

        return (array) $stats;
    }

    public function revenueStats(): array
    {
        $stats = Payment::toBase()->selectRaw("
            COALESCE(SUM(amount) FILTER (WHERE status = 'paid'), 0)                                                                                      AS total_revenue,
            COALESCE(SUM(amount) FILTER (WHERE status = 'paid' AND DATE_TRUNC('month', paid_at) = DATE_TRUNC('month', NOW())), 0)                        AS this_month,
            COALESCE(SUM(amount) FILTER (WHERE status = 'paid' AND DATE_TRUNC('month', paid_at) = DATE_TRUNC('month', NOW() - INTERVAL '1 month')), 0)   AS last_month,
            COUNT(*) FILTER (WHERE status = 'pending')                                                                                                   AS pending_payments
        ")->first();

        $stats = (array) $stats;
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
