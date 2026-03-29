<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatusEnum;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\DashboardService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController
{
    use ApiResponseTrait;

    public function __construct(public DashboardService $dashboardService) {}

    public function index(): JsonResponse
    {
        return $this->success([
            'members' => $this->dashboardService->memberStats(),
            'subscriptions' => $this->dashboardService->subscriptionStats(),
            'revenue' => $this->dashboardService->revenueStats(),
            'attendance' => $this->dashboardService->attendanceStats(),
        ], 'Dashboard data retrieved successfully.');
    }

    /**
     * Revenue analytics with chart data.
     */
    public function revenue(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', now()->year);

        $monthlyRevenue = Payment::selectRaw("
            EXTRACT(MONTH FROM paid_at)::int AS month,
            TO_CHAR(paid_at, 'Mon')          AS month_name,
            COUNT(*)                          AS transactions,
            COALESCE(SUM(amount), 0)          AS total
        ")
            ->whereYear('paid_at', $year)
            ->where('status', PaymentStatusEnum::Paid)
            ->groupByRaw("EXTRACT(MONTH FROM paid_at), TO_CHAR(paid_at, 'Mon')")
            ->orderByRaw('EXTRACT(MONTH FROM paid_at)')
            ->get();

        $months = collect(range(1, 12))->map(fn ($m) => [
            'month' => $m,
            'month_name' => now()->setMonth($m)->format('M'),
            'transactions' => 0,
            'total' => 0,
        ]);

        $merged = $months->map(function ($month) use ($monthlyRevenue) {
            $found = $monthlyRevenue->firstWhere('month', $month['month']);

            return $found ? [
                'month' => $found->month,
                'month_name' => $found->month_name,
                'transactions' => $found->transactions,
                'total' => (float) $found->total,
            ] : $month;
        });

        $thisMonth = Payment::whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->where('status', PaymentStatusEnum::Paid)
            ->sum('amount');

        $lastMonth = Payment::whereMonth('paid_at', now()->subMonth()->month)
            ->whereYear('paid_at', now()->subMonth()->year)
            ->where('status', PaymentStatusEnum::Paid)
            ->sum('amount');

        $growth = $lastMonth > 0
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2)
            : 100;

        return $this->success([
            'year' => $year,
            'monthly_chart' => $merged,
            'summary' => [
                'this_month' => (float) $thisMonth,
                'last_month' => (float) $lastMonth,
                'growth' => $growth,
                'growth_type' => $growth >= 0 ? 'increase' : 'decrease',
                'yearly_total' => (float) Payment::whereYear('paid_at', $year)
                    ->where('status', PaymentStatusEnum::Paid)->sum('amount'),
            ],
            'by_method' => Payment::selectRaw('
                payment_method,
                COUNT(*)             AS transactions,
                COALESCE(SUM(amount), 0) AS total
            ')
                ->where('status', PaymentStatusEnum::Paid)
                ->whereYear('paid_at', $year)
                ->groupBy('payment_method')
                ->get(),
        ], 'Revenue analytics retrieved successfully.');
    }

    /**
     * Member growth analytics.
     */
    public function members(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', now()->year);

        $monthlyGrowth = Member::selectRaw("
            EXTRACT(MONTH FROM created_at)::int AS month,
            TO_CHAR(created_at, 'Mon')           AS month_name,
            COUNT(*)                              AS new_members
        ")
            ->whereYear('created_at', $year)
            ->groupByRaw("EXTRACT(MONTH FROM created_at), TO_CHAR(created_at, 'Mon')")
            ->orderByRaw('EXTRACT(MONTH FROM created_at)')
            ->get();

        $months = collect(range(1, 12))->map(fn ($m) => [
            'month' => $m,
            'month_name' => now()->setMonth($m)->format('M'),
            'new_members' => 0,
        ]);

        $merged = $months->map(function ($month) use ($monthlyGrowth) {
            $found = $monthlyGrowth->firstWhere('month', $month['month']);

            return $found ? [
                'month' => $found->month,
                'month_name' => $found->month_name,
                'new_members' => (int) $found->new_members,
            ] : $month;
        });

        $genderBreakdown = Member::selectRaw('
            gender,
            COUNT(*) AS total
        ')
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->get();

        $statusBreakdown = Member::selectRaw('
            status,
            COUNT(*) AS total
        ')
            ->groupBy('status')
            ->get();

        $thisMonth = Member::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $lastMonth = Member::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $growth = $lastMonth > 0
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2)
            : 100;

        return $this->success([
            'year' => $year,
            'monthly_chart' => $merged,
            'gender_breakdown' => $genderBreakdown,
            'status_breakdown' => $statusBreakdown,
            'summary' => [
                'total' => Member::count(),
                'this_month' => $thisMonth,
                'last_month' => $lastMonth,
                'growth' => $growth,
                'growth_type' => $growth >= 0 ? 'increase' : 'decrease',
            ],
        ], 'Member analytics retrieved successfully.');
    }

    public function attendance(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        $dailyAttendance = Attendance::selectRaw('
            DATE(check_in)          AS date,
            COUNT(*)                AS total,
            COUNT(check_out)        AS checked_out,
            ROUND(AVG(
                EXTRACT(EPOCH FROM (check_out - check_in)) / 60
            ))::int                 AS avg_duration_minutes
        ')
            ->where('check_in', '>=', now()->subDays($days))
            ->groupByRaw('DATE(check_in)')
            ->orderByRaw('DATE(check_in)')
            ->get();

        $peakHours = Attendance::selectRaw('
            EXTRACT(HOUR FROM check_in)::int AS hour,
            COUNT(*)                          AS visits
        ')
            ->where('check_in', '>=', now()->subDays(30))
            ->groupByRaw('EXTRACT(HOUR FROM check_in)')
            ->orderByRaw('EXTRACT(HOUR FROM check_in)')
            ->get()
            ->map(fn ($h) => [
                'hour' => $h->hour,
                'hour_label' => sprintf('%02d:00', $h->hour),
                'visits' => (int) $h->visits,
            ]);

        $today = Attendance::selectRaw('
            COUNT(*)              AS total,
            COUNT(check_out)      AS checked_out,
            COUNT(*) - COUNT(check_out) AS still_in
        ')
            ->whereDate('check_in', today())
            ->first();

        $thisWeek = Attendance::whereBetween('check_in', [
            now()->startOfWeek(), now()->endOfWeek(),
        ])->count();

        $lastWeek = Attendance::whereBetween('check_in', [
            now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek(),
        ])->count();

        return $this->success([
            'today' => [
                'total' => (int) $today->total,
                'checked_out' => (int) $today->checked_out,
                'still_in' => (int) $today->still_in,
            ],
            'weekly_comparison' => [
                'this_week' => $thisWeek,
                'last_week' => $lastWeek,
                'change' => $thisWeek - $lastWeek,
            ],
            'daily_chart' => $dailyAttendance,
            'peak_hours' => $peakHours,
        ], 'Attendance analytics retrieved successfully.');
    }

    /**
     * Subscription analytics.
     */
    public function subscriptions(): JsonResponse
    {
        $expiringSoon = Subscription::with(['member.user', 'plan'])
            ->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(7)])
            ->orderBy('end_date')
            ->get()
            ->map(fn ($sub) => [
                'member' => $sub->member->user->name,
                'membership_number' => $sub->member->membership_number,
                'plan' => $sub->plan->name,
                'end_date' => $sub->end_date->format('Y-m-d'),
                'days_remaining' => $sub->daysRemaining(),
            ]);

        $stats = Subscription::toBase()->selectRaw("
            COUNT(*)                                            AS total,
            COUNT(*) FILTER (WHERE status = 'active')          AS active,
            COUNT(*) FILTER (WHERE status = 'expired')         AS expired,
            COUNT(*) FILTER (WHERE status = 'frozen')          AS frozen,
            COUNT(*) FILTER (WHERE status = 'cancelled')       AS cancelled,
            COUNT(*) FILTER (WHERE end_date BETWEEN NOW() AND NOW() + INTERVAL '7 days' AND status = 'active')  AS expiring_this_week,
            COUNT(*) FILTER (WHERE end_date BETWEEN NOW() AND NOW() + INTERVAL '30 days' AND status = 'active') AS expiring_this_month
        ")->first();

        $popularPlans = Subscription::selectRaw('
            plan_id,
            COUNT(*) AS total_subscriptions
        ')
            ->with('plan:id,name,price')
            ->groupBy('plan_id')
            ->orderByDesc('total_subscriptions')
            ->limit(5)
            ->get()
            ->map(fn ($sub) => [
                'plan' => $sub->plan->name,
                'price' => $sub->plan->price,
                'total_subscriptions' => (int) $sub->total_subscriptions,
            ]);

        return $this->success([
            'stats' => (array) $stats,
            'popular_plans' => $popularPlans,
            'expiring_soon' => $expiringSoon,
        ], 'Subscription analytics retrieved successfully.');
    }

    public function paymentStats(): JsonResponse
    {
        $paid     = PaymentStatusEnum::Paid->value;
        $pending  = PaymentStatusEnum::Pending->value;
        $refunded = PaymentStatusEnum::Refunded->value;

        $stats = Payment::toBase()->selectRaw("
        COUNT(*)                                                                                                        AS total_transactions,
        COALESCE(SUM(amount) FILTER (WHERE status = ?), 0)                                                             AS total_revenue,
        COALESCE(SUM(amount) FILTER (WHERE status = ?), 0)                                                             AS pending_amount,
        COALESCE(SUM(amount) FILTER (WHERE status = ?), 0)                                                             AS refunded_amount,
        COUNT(*)         FILTER (WHERE DATE_TRUNC('month', paid_at) = DATE_TRUNC('month', NOW()))                      AS this_month_transactions,
        COALESCE(SUM(amount) FILTER (WHERE DATE_TRUNC('month', paid_at) = DATE_TRUNC('month', NOW()) AND status = ?), 0) AS this_month_revenue
    ", [$paid, $pending, $refunded, $paid])->first();

        return $this->success((array) $stats, 'Payment stats retrieved successfully.');
    }
}
