<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Expense;
use App\Models\SalaryPayment;
use App\Models\CommissionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialDashboardController extends Controller
{
    /**
     * Get financial dashboard overview.
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'month'); // day, week, month, year
        $startDate = $this->getStartDate($period);
        $endDate = now();

        $metrics = [
            'revenue' => $this->getRevenueMetrics($startDate, $endDate),
            'expenses' => $this->getExpenseMetrics($startDate, $endDate),
            'profitability' => $this->getProfitabilityMetrics($startDate, $endDate),
            'collections' => $this->getCollectionMetrics($startDate, $endDate),
            'payments' => $this->getPaymentMetrics($startDate, $endDate),
            'trends' => $this->getTrendData($period, $startDate, $endDate),
        ];

        return response()->json([
            'success' => true,
            'data' => $metrics,
            'period' => $period,
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ]
        ]);
    }

    /**
     * Get revenue metrics.
     */
    private function getRevenueMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $collections = Collection::whereBetween('collected_at', [$startDate, $endDate]);

        $totalRevenue = $collections->sum('amount');
        $totalCollections = $collections->count();
        $averageCollectionValue = $totalCollections > 0 ? $totalRevenue / $totalCollections : 0;

        // Revenue by delivery type (home delivery vs stop desk)
        $revenueByType = Collection::with('parcel')
            ->whereBetween('collected_at', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($collection) {
                return $collection->parcel ? $collection->parcel->delivery_type : 'unknown';
            })
            ->map(function ($group) {
                return $group->sum('amount');
            })
            ->toArray();

        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_collections' => $totalCollections,
            'average_collection_value' => round($averageCollectionValue, 2),
            'revenue_by_type' => array_map(fn($amount) => round($amount, 2), $revenueByType),
        ];
    }

    /**
     * Get expense metrics.
     */
    private function getExpenseMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate]);

        $totalExpenses = $expenses->sum('amount');
        $expensesByCategory = $expenses->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get()
            ->pluck('total', 'category')
            ->toArray();

        $expensesByStatus = $expenses->select('status', DB::raw('SUM(amount) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        return [
            'total_expenses' => round($totalExpenses, 2),
            'expenses_by_category' => array_map(fn($amount) => round($amount, 2), $expensesByCategory),
            'expenses_by_status' => array_map(fn($amount) => round($amount, 2), $expensesByStatus),
            'salary_expenses' => round($expenses->where('category', 'salary')->sum('amount'), 2),
            'commission_expenses' => round($expenses->where('category', 'commission')->sum('amount'), 2),
        ];
    }

    /**
     * Get profitability metrics.
     */
    private function getProfitabilityMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $revenue = Collection::whereBetween('collected_at', [$startDate, $endDate])
            ->sum('amount');

        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');

        $netProfit = $revenue - $expenses;
        $profitMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;

        return [
            'gross_revenue' => round($revenue, 2),
            'total_expenses' => round($expenses, 2),
            'net_profit' => round($netProfit, 2),
            'profit_margin' => round($profitMargin, 2),
        ];
    }

    /**
     * Get collection metrics.
     */
    private function getCollectionMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $collections = Collection::whereBetween('collected_at', [$startDate, $endDate]);

        $totalCollections = $collections->count();
        
        // Group by delivery type
        $homeDeliveryCollections = $collections->whereHas('parcel', function ($q) {
            $q->where('delivery_type', 'home_delivery');
        })->count();
        
        $stopDeskCollections = $collections->whereHas('parcel', function ($q) {
            $q->where('delivery_type', 'stop_desk');
        })->count();

        return [
            'total_collections' => $totalCollections,
            'home_delivery_collections' => $homeDeliveryCollections,
            'stop_desk_collections' => $stopDeskCollections,
            'average_collection_amount' => $totalCollections > 0 ? round($collections->avg('amount'), 2) : 0,
        ];
    }

    /**
     * Get payment metrics.
     */
    private function getPaymentMetrics(Carbon $startDate, Carbon $endDate): array
    {
        // Salary payments
        $salaryPayments = SalaryPayment::whereBetween('payment_date', [$startDate, $endDate]);
        $totalSalaryPayments = $salaryPayments->sum('amount');
        $pendingSalaryPayments = $salaryPayments->where('status', 'pending')->sum('amount');

        // Commission payments
        $commissionPayments = CommissionPayment::whereBetween('payment_date', [$startDate, $endDate]);
        $totalCommissionPayments = $commissionPayments->sum('amount');
        $pendingCommissionPayments = $commissionPayments->where('status', 'pending')->sum('amount');

        return [
            'salary_payments' => [
                'total' => round($totalSalaryPayments, 2),
                'pending' => round($pendingSalaryPayments, 2),
                'paid' => round($totalSalaryPayments - $pendingSalaryPayments, 2),
            ],
            'commission_payments' => [
                'total' => round($totalCommissionPayments, 2),
                'pending' => round($pendingCommissionPayments, 2),
                'paid' => round($totalCommissionPayments - $pendingCommissionPayments, 2),
            ],
            'total_staff_costs' => round($totalSalaryPayments + $totalCommissionPayments, 2),
        ];
    }

    /**
     * Get trend data for charts.
     */
    private function getTrendData(string $period, Carbon $startDate, Carbon $endDate): array
    {
        $dateFormat = $this->getDateFormat($period);
        $groupBy = $this->getGroupByClause($period);

        // Revenue trend
        $revenueTrend = Collection::whereBetween('collected_at', [$startDate, $endDate])
            ->select(
                DB::raw($groupBy . ' as period'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as collections')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) use ($dateFormat) {
                return [
                    'period' => Carbon::createFromFormat($dateFormat, $item->period)->format('Y-m-d'),
                    'revenue' => round($item->revenue, 2),
                    'collections' => $item->collections,
                ];
            });

        // Expense trend
        $expenseTrend = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->select(
                DB::raw($groupBy . ' as period'),
                DB::raw('SUM(amount) as expenses')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) use ($dateFormat) {
                return [
                    'period' => Carbon::createFromFormat($dateFormat, $item->period)->format('Y-m-d'),
                    'expenses' => round($item->expenses, 2),
                ];
            });

        return [
            'revenue' => $revenueTrend,
            'expenses' => $expenseTrend,
        ];
    }

    /**
     * Get start date based on period.
     */
    private function getStartDate(string $period): Carbon
    {
        return match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };
    }

    /**
     * Get date format for grouping.
     */
    private function getDateFormat(string $period): string
    {
        return match ($period) {
            'day' => 'Y-m-d H',
            'week' => 'Y-m-d',
            'month' => 'Y-m-d',
            'year' => 'Y-m',
            default => 'Y-m-d',
        };
    }

    /**
     * Get GROUP BY clause for SQL.
     */
    private function getGroupByClause(string $period): string
    {
        return match ($period) {
            'day' => "DATE_FORMAT(completed_at, '%Y-%m-%d %H')",
            'week' => "DATE(completed_at)",
            'month' => "DATE(completed_at)",
            'year' => "DATE_FORMAT(completed_at, '%Y-%m')",
            default => "DATE(completed_at)",
        };
    }

    /**
     * Get financial summary for a specific period.
     */
    public function summary(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $summary = [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'revenue' => $this->getRevenueMetrics($startDate, $endDate),
            'expenses' => $this->getExpenseMetrics($startDate, $endDate),
            'profitability' => $this->getProfitabilityMetrics($startDate, $endDate),
            'collections' => $this->getCollectionMetrics($startDate, $endDate),
            'payments' => $this->getPaymentMetrics($startDate, $endDate),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}