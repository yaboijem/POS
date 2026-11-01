<?php

namespace App\Http\Controllers;

use App\Services\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, SalesReportService $reports): View
    {
        [$from, $to, $grouping] = $this->resolvedFilters($request);

        $summary = $reports->summary($from, $to);
        $series = $reports->series($from, $to, $grouping);
        $daily = $reports->dailySeries($from, $to);

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'grouping' => $grouping,
            'summary' => $summary,
            'series' => $series,
            'daily' => $daily,
            'categories' => $reports->categoryTotals($from, $to),
            'products' => $reports->topProducts($from, $to, 10),
            'payments' => $reports->paymentMix($from, $to),
            'cashiers' => $reports->cashierPerformance($from, $to),
        ]);
    }

    public function export(Request $request, SalesReportService $reports): StreamedResponse
    {
        [$from, $to] = $this->resolvedFilters($request);

        $summary = $reports->summary($from, $to);
        $daily = $reports->dailySeries($from, $to);
        $categories = $reports->categoryTotals($from, $to);
        $products = $reports->topProducts($from, $to, 50);
        $payments = $reports->paymentMix($from, $to);
        $cashiers = $reports->cashierPerformance($from, $to);

        $filename = 'sales-report-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return Response::streamDownload(function () use ($from, $to, $summary, $daily, $categories, $products, $payments, $cashiers) {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");

            $this->writeRow($output, ['Sales report']);
            $this->writeRow($output, ['From', $from->toDateString()]);
            $this->writeRow($output, ['To', $to->toDateString()]);
            $this->writeRow($output, []);
            $this->writeRow($output, ['Summary']);
            $this->writeRow($output, ['Transactions', $summary['transactions']]);
            $this->writeRow($output, ['Gross sales', number_format($summary['gross'], 2, '.', '')]);
            $this->writeRow($output, ['Net of tax', number_format($summary['net'], 2, '.', '')]);
            $this->writeRow($output, ['Tax', number_format($summary['tax'], 2, '.', '')]);
            $this->writeRow($output, ['Discounts', number_format($summary['discounts'], 2, '.', '')]);
            $this->writeRow($output, ['Average sale', number_format($summary['average'], 2, '.', '')]);
            $this->writeRow($output, ['Items sold', quantity($summary['items'])]);

            $this->writeRow($output, []);
            $this->writeRow($output, ['Sales by day']);
            $this->writeRow($output, ['Date', 'Transactions', 'Total']);
            foreach ($daily['keys'] as $index => $key) {
                $this->writeRow($output, [$key, $daily['transactions'][$index], number_format($daily['values'][$index], 2, '.', '')]);
            }

            $this->writeRow($output, []);
            $this->writeRow($output, ['Sales by category']);
            $this->writeRow($output, ['Category', 'Quantity', 'Sales']);
            foreach ($categories as $category) {
                $this->writeRow($output, [$category->name, quantity($category->quantity), number_format((float) $category->total, 2, '.', '')]);
            }

            $this->writeRow($output, []);
            $this->writeRow($output, ['Payment methods']);
            $this->writeRow($output, ['Method', 'Transactions', 'Total']);
            foreach ($payments as $payment) {
                $this->writeRow($output, [payment_method_label($payment->payment_method), $payment->transactions, number_format((float) $payment->total, 2, '.', '')]);
            }

            $this->writeRow($output, []);
            $this->writeRow($output, ['Cashier performance']);
            $this->writeRow($output, ['Cashier', 'Transactions', 'Total']);
            foreach ($cashiers as $cashier) {
                $this->writeRow($output, [$cashier->name, $cashier->transactions, number_format((float) $cashier->total, 2, '.', '')]);
            }

            $this->writeRow($output, []);
            $this->writeRow($output, ['Top products by quantity']);
            $this->writeRow($output, ['Product', 'Unit', 'Quantity', 'Sales']);
            foreach ($products as $product) {
                $this->writeRow($output, [$product->product_name, $product->unit, quantity($product->quantity), number_format((float) $product->total, 2, '.', '')]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    protected function resolvedFilters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'group' => ['nullable', Rule::in(['day', 'week', 'month'])],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now()->toDateString())->endOfDay();

        $grouping = $validated['group'] ?? $this->defaultGrouping($from, $to);

        return [$from, $to, $grouping];
    }

    protected function defaultGrouping(Carbon $from, Carbon $to): string
    {
        $days = $from->diffInDays($to);

        return $days <= 31 ? 'day' : ($days <= 120 ? 'week' : 'month');
    }

    /**
     * @param  resource  $output
     * @param  array<int, mixed>  $row
     */
    protected function writeRow($output, array $row): void
    {
        fputcsv($output, array_map(fn ($value) => $this->csvSafe($value), $row), ',', '"', '');
    }

    protected function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
