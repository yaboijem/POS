<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $query = $this->filteredQuery($request, $filters);

        $summaryRow = (clone $query)
            ->selectRaw('COUNT(*) AS transactions, COALESCE(SUM(total), 0) AS gross, COALESCE(SUM(discount_amount), 0) AS discounts')
            ->first();

        $sort = $filters['sort'];
        $query = match ($sort) {
            'oldest' => $query->orderBy('sold_at')->orderBy('id'),
            'total_desc' => $query->orderByDesc('total')->orderByDesc('sold_at'),
            'total_asc' => $query->orderBy('total')->orderByDesc('sold_at'),
            default => $query->orderByDesc('sold_at')->orderByDesc('id'),
        };

        $sales = $query
            ->with(['cashier', 'payment'])
            ->withCount('items')
            ->paginate(15)
            ->withQueryString();

        $transactions = (int) ($summaryRow->transactions ?? 0);
        $gross = (float) ($summaryRow->gross ?? 0);

        return view('sales.index', [
            'sales' => $sales,
            'filters' => $filters,
            'summary' => [
                'transactions' => $transactions,
                'gross' => $gross,
                'discounts' => (float) ($summaryRow->discounts ?? 0),
                'average' => $transactions > 0 ? round($gross / $transactions, 2) : 0.0,
            ],
            'cashiers' => $request->user()->isAdmin()
                ? User::query()->orderBy('name')->get(['id', 'name', 'is_active'])
                : collect(),
            'paymentMethods' => payment_method_options(),
            'isAdmin' => $request->user()->isAdmin(),
        ]);
    }

    public function show(Request $request, Sale $sale): View
    {
        $this->authorize('view', $sale);

        $sale->load(['items', 'payment', 'cashier']);

        return view('sales.show', [
            'sale' => $sale,
            'auditLogs' => AuditLog::query()
                ->where('auditable_type', $sale->getMorphClass())
                ->where('auditable_id', $sale->getKey())
                ->with('user')
                ->latest()
                ->limit(20)
                ->get(),
            'isAdmin' => $request->user()->isAdmin(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->filteredQuery($request, $filters)
            ->with(['cashier', 'items'])
            ->orderBy('sold_at');

        $filename = 'sales-'.now()->format('Ymd-His').'.csv';

        return Response::streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Transaction number',
                'Date and time',
                'Cashier',
                'Payment method',
                'Items',
                'Subtotal',
                'Discount',
                'Tax',
                'Total',
                'Status',
            ], ',', '"', '');

            $query->chunk(500, function ($sales) use ($output) {
                foreach ($sales as $sale) {
                    $items = $sale->items
                        ->map(fn ($item) => quantity($item->quantity).' x '.$item->product_name)
                        ->implode('; ');

                    fputcsv($output, array_map(fn ($value) => $this->csvSafe($value), [
                        $sale->transaction_number,
                        $sale->sold_at->format('Y-m-d H:i:s'),
                        $sale->cashier?->name ?? 'Deleted user',
                        payment_method_label($sale->payment_method, $sale->payment_label),
                        $items,
                        $sale->subtotal,
                        $sale->discount_amount,
                        $sale->tax_amount,
                        $sale->total,
                        $sale->status,
                    ]), ',', '"', '');
                }
            });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{search: string, date_from: string|null, date_to: string|null, cashier: string, payment_method: string, sort: string}
     */
    protected function filters(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'cashier' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'payment_method' => ['nullable', Rule::in(array_keys(payment_method_options()))],
            'sort' => ['nullable', Rule::in(['latest', 'oldest', 'total_desc', 'total_asc'])],
        ]);

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'cashier' => (string) ($validated['cashier'] ?? ''),
            'payment_method' => (string) ($validated['payment_method'] ?? ''),
            'sort' => $validated['sort'] ?? 'latest',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function filteredQuery(Request $request, array $filters): Builder
    {
        return Sale::query()
            ->completed()
            ->when(! $request->user()->isAdmin(), fn (Builder $query) => $query->where('cashier_id', $request->user()->id))
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = $filters['search'];
                $query->where(function (Builder $query) use ($search) {
                    $query->where('transaction_number', 'like', '%'.$search.'%')
                        ->orWhere('reference', 'like', '%'.$search.'%')
                        ->orWhere('payment_reference', 'like', '%'.$search.'%')
                        ->orWhereHas('cashier', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['date_from'], fn (Builder $query, $date) => $query->whereDate('sold_at', '>=', $date))
            ->when($filters['date_to'], fn (Builder $query, $date) => $query->whereDate('sold_at', '<=', $date))
            ->when(
                $request->user()->isAdmin() && $filters['cashier'] !== '',
                fn (Builder $query) => $query->where('cashier_id', (int) $filters['cashier'])
            )
            ->when($filters['payment_method'] !== '', fn (Builder $query) => $query->where('payment_method', $filters['payment_method']));
    }

    protected function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
