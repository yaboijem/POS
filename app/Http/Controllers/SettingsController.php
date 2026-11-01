<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * @var list<string>
     */
    protected array $keys = [
        'business_name',
        'business_address',
        'business_phone',
        'business_email',
        'logo_path',
        'receipt_footer',
        'low_stock_threshold',
        'payment_methods',
        'tax_enabled',
        'tax_rate',
        'tax_inclusive',
    ];

    public function index(): View
    {
        $logoPath = setting('logo_path');

        return view('settings.index', [
            'logoUrl' => $logoPath ? asset('storage/'.$logoPath) : null,
            'enabledPaymentMethods' => enabled_payment_methods(),
            'paymentMethods' => payment_method_options(),
            'taxEnabled' => (string) setting('tax_enabled', '0') === '1',
            'taxRate' => setting('tax_rate', '0'),
            'taxInclusive' => (string) setting('tax_inclusive', '0') === '1',
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $oldValues = BusinessSetting::query()->whereIn('key', $this->keys)->pluck('value', 'key')->all();
        $logoPath = setting('logo_path');

        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $request->file('logo')->store('business-logos', 'public');
        } elseif ($request->boolean('remove_logo') && $logoPath) {
            Storage::disk('public')->delete($logoPath);
            $logoPath = null;
        }

        $taxEnabled = $request->boolean('tax_enabled');
        $taxRate = $taxEnabled ? number_format((float) $validated['tax_rate'], 2, '.', '') : '0';

        $paymentMethods = array_values(array_unique($validated['payment_methods']));
        $paymentMethods = array_values(array_intersect(array_keys(payment_method_options()), $paymentMethods));

        $values = [
            'business_name' => trim($validated['business_name']),
            'business_address' => $this->nullableTrim($validated['business_address'] ?? null),
            'business_phone' => $this->nullableTrim($validated['business_phone'] ?? null),
            'business_email' => $this->nullableTrim($validated['business_email'] ?? null),
            'logo_path' => $logoPath,
            'receipt_footer' => $this->nullableTrim($validated['receipt_footer'] ?? null),
            'low_stock_threshold' => (string) $validated['low_stock_threshold'],
            'tax_enabled' => $taxEnabled ? '1' : '0',
            'tax_rate' => $taxRate,
            'tax_inclusive' => $taxEnabled && $request->boolean('tax_inclusive') ? '1' : '0',
        ];

        foreach ($values as $key => $value) {
            BusinessSetting::put($key, $value);
        }

        BusinessSetting::put('payment_methods', $paymentMethods, 'json');

        $newValues = BusinessSetting::query()->whereIn('key', $this->keys)->pluck('value', 'key')->all();

        AuditLog::record(
            $request->user(),
            'settings.updated',
            'Updated business and POS settings.',
            null,
            $oldValues,
            $newValues
        );

        return redirect()
            ->route('settings.index')
            ->with('success', 'Settings saved.');
    }

    protected function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
