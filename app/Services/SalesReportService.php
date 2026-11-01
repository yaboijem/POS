<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    /**
     * @return array{gross: float, net: float, tax: float, discounts: float, subtotal: float, transactions: int, average: float, items: float}
     */
    public function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $row = Sale::query()
            ->completed()
            ->whereBetween('sold_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('COUNT(*) AS transactions, COALESCE(SUM(total), 0) AS gross, COALESCE(SUM(tax_amount), 0) AS tax, COALESCE(SUM(discount_amount), 0) AS discounts, COALESCE(SUM(subtotal), 0) AS subtotal')
            ->first();

        $transactions = (int) ($row->transactions ?? 0);
        $gross = (float) ($row->gross ?? 0);
        $tax = (float) ($row->tax ?? 0);

        $items = (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereBetween('sales.sold_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->sum('sale_items.quantity');

        return [
            'gross' => $gross,
            'net' => round($gross - $tax, 2),
            'tax' => $tax,
            'discounts' => (float) ($row->discounts ?? 0),
            'subtotal' => (float) ($row->subtotal ?? 0),
            'transactions' => $transactions,
            'average' => $transactions > 0 ? round($gross / $transactions, 2) : 0.0,
            'items' => $items,
        ];
    }

    /**
     * @return array{labels: list<string>, keys: list<string>, values: list<float>, transactions: list<int>}
     */
    public function dailySeries(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = Sale::query()
            ->completed()
            ->whereBetween('sold_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('DATE(sold_at) AS day, COALESCE(SUM(total), 0) AS total, COUNT(*) AS transactions')
            ->groupBy('day')
            ->get()
            ->keyBy(fn ($row) => (string) $row->day);

        $labels = [];
        $keys = [];
        $values = [];
        $transactions = [];

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $date) {
            $key = $date->format('Y-m-d');
            $keys[] = $key;
            $labels[] = $date->format('D, M j');
            $values[] = (float) ($rows[$key]->total ?? 0);
            $transactions[] = (int) ($rows[$key]->transactions ?? 0);
        }

        return compact('labels', 'keys', 'values', 'transactions');
    }

    /**
     * @return array{labels: list<string>, values: list<float>, transactions: list<int>}
     */
    public function series(CarbonInterface $from, CarbonInterface $to, string $grouping = 'day'): array
    {
        $daily = $this->dailySeries($from, $to);

        if ($grouping === 'day') {
            return [
                'labels' => $daily['labels'],
                'values' => $daily['values'],
                'transactions' => $daily['transactions'],
            ];
        }

        $buckets = [];

        foreach ($daily['keys'] as $index => $key) {
            $date = Carbon::parse($key);
            $bucket = $grouping === 'week'
                ? $date->copy()->startOfWeek()->format('Y-m-d')
                : $date->copy()->format('Y-m');

            $buckets[$bucket] ??= ['value' => 0.0, 'transactions' => 0];
            $buckets[$bucket]['value'] += $daily['values'][$index];
            $buckets[$bucket]['transactions'] += $daily['transactions'][$index];
        }

        $labels = [];
        $values = [];
        $transactions = [];

        foreach ($buckets as $key => $bucket) {
            $labels[] = $grouping === 'week'
                ? Carbon::parse($key)->format('M j')
                : Carbon::parse($key.'-01')->format('M Y');
            $values[] = round($bucket['value'], 2);
            $transactions[] = $bucket['transactions'];
        }

        return compact('labels', 'values', 'transactions');
    }

    /**
     * @return Collection<int, object>
     */
    public function categoryTotals(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereBetween('sales.sold_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupByRaw("COALESCE(sale_items.category_name, categories.name, 'Uncategorized'), COALESCE(sale_items.category_color, categories.color, '#F97316')")
            ->select(
                DB::raw("COALESCE(sale_items.category_name, categories.name, 'Uncategorized') AS name"),
                DB::raw("COALESCE(sale_items.category_color, categories.color, '#F97316') AS color"),
                DB::raw('COALESCE(SUM(sale_items.line_total), 0) AS total'),
                DB::raw('COALESCE(SUM(sale_items.quantity), 0) AS quantity')
            )
            ->orderByDesc('total')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function topProducts(CarbonInterface $from, CarbonInterface $to, int $limit = 5): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->whereBetween('sales.sold_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('sale_items.product_id')
            ->select(
                'sale_items.product_id',
                DB::raw('MAX(sale_items.product_name) AS product_name'),
                DB::raw('MAX(sale_items.unit) AS unit'),
                DB::raw('COALESCE(SUM(sale_items.quantity), 0) AS quantity'),
                DB::raw('COALESCE(SUM(sale_items.line_total), 0) AS total')
            )
            ->orderByDesc('quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function paymentMix(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Sale::query()
            ->completed()
            ->whereBetween('sold_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('payment_method')
            ->select(
                'payment_method',
                DB::raw('COUNT(*) AS transactions'),
                DB::raw('COALESCE(SUM(total), 0) AS total')
            )
            ->orderByDesc('total')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function cashierPerformance(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Sale::query()
            ->completed()
            ->join('users', 'users.id', '=', 'sales.cashier_id')
            ->whereBetween('sales.sold_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('users.id', 'users.name')
            ->select(
                'users.name',
                DB::raw('COUNT(*) AS transactions'),
                DB::raw('COALESCE(SUM(sales.total), 0) AS total')
            )
            ->orderByDesc('total')
            ->get();
    }
}
