<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\SalesReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SalesReportService $reports): View
    {
        $period = in_array($request->query('period'), ['daily', 'weekly', 'monthly'], true)
            ? $request->query('period')
            : 'daily';

        [$from, $to, $grouping, $periodLabel] = $this->periodRange($period);

        $today = $reports->summary(now()->startOfDay(), now()->endOfDay());
        $yesterday = $reports->summary(now()->subDay()->startOfDay(), now()->subDay()->endOfDay());
        $series = $reports->series($from, $to, $grouping);
        $categories = $reports->categoryTotals($from, $to);
        $topProducts = $reports->topProducts($from, $to, 5);
        $bestSellers = $reports->topProducts(now()->subDays(6)->startOfDay(), now()->endOfDay(), 5);

        $lowStockThreshold = (float) setting('low_stock_threshold', 5);
        $lowStockCount = Product::query()
            ->tracked()
            ->whereColumn('stock_quantity', '<=', DB::raw('COALESCE(low_stock_threshold, '.$lowStockThreshold.')'))
            ->count();

        return view('dashboard', [
            'user' => $request->user(),
            'period' => $period,
            'periodLabel' => $periodLabel,
            'today' => $today,
            'yesterday' => $yesterday,
            'series' => $series,
            'categories' => $categories,
            'topProducts' => $topProducts,
            'bestSellers' => $bestSellers,
            'productCount' => Product::query()->count(),
            'lowStockCount' => $lowStockCount,
            'grossDelta' => $this->delta($today['gross'], $yesterday['gross']),
            'netDelta' => $this->delta($today['net'], $yesterday['net']),
            'transactionsDelta' => $this->delta($today['transactions'], $yesterday['transactions']),
            'averageDelta' => $this->delta($today['average'], $yesterday['average']),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string, 3: string}
     */
    protected function periodRange(string $period): array
    {
        return match ($period) {
            'weekly' => [
                now()->subWeeks(7)->startOfWeek(),
                now()->endOfDay(),
                'week',
                now()->subWeeks(7)->startOfWeek()->format('M j').' – '.now()->format('M j, Y'),
            ],
            'monthly' => [
                now()->subMonths(5)->startOfMonth(),
                now()->endOfDay(),
                'month',
                now()->subMonths(5)->format('M Y').' – '.now()->format('M Y'),
            ],
            default => [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
                'day',
                now()->subDays(6)->format('M j').' – '.now()->format('M j, Y'),
            ],
        };
    }

    /**
     * @return array{value: string, positive: bool}
     */
    protected function delta(float|int $current, float|int $previous): array
    {
        if ((float) $previous === 0.0) {
            return [
                'value' => (float) $current > 0 ? 'New' : 'No change',
                'positive' => (float) $current >= 0,
            ];
        }

        $change = (((float) $current - (float) $previous) / (float) $previous) * 100;

        return [
            'value' => ($change >= 0 ? '+' : '').number_format($change, 1).'%',
            'positive' => $change >= 0,
        ];
    }
}
