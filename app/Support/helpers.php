<?php

use App\Models\BusinessSetting;

if (! function_exists('setting')) {
    /**
     * Read a business setting. Safe before migrations (returns the default).
     */
    function setting(string $key, mixed $default = null): mixed
    {
        try {
            $value = BusinessSetting::query()->where('key', $key)->value('value');
        } catch (Throwable) {
            return $default;
        }

        return $value ?? $default;
    }
}

if (! function_exists('peso')) {
    /**
     * Format an amount as Philippine pesos: ₱1,234.50.
     */
    function peso(string|int|float|null $amount): string
    {
        $value = (float) ($amount ?? 0);
        $formatted = number_format(abs($value), 2, '.', ',');

        return ($value < 0 ? '-₱' : '₱').$formatted;
    }
}

if (! function_exists('quantity')) {
    /**
     * Format a quantity without noisy trailing zeroes: 2, 2.5, 0.125.
     */
    function quantity(string|int|float|null $value): string
    {
        $number = (float) ($value ?? 0);

        if (abs($number - round($number)) < 0.00001) {
            return number_format($number, 0);
        }

        return rtrim(rtrim(number_format($number, 3, '.', ''), '0'), '.');
    }
}

if (! function_exists('to_cents')) {
    /**
     * Convert a peso amount to integer cents for exact arithmetic.
     */
    function to_cents(string|int|float|null $amount): int
    {
        return (int) round(((float) ($amount ?? 0)) * 100);
    }
}

if (! function_exists('from_cents')) {
    /**
     * Convert integer cents to a decimal string suitable for DB storage.
     */
    function from_cents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}

if (! function_exists('payment_method_options')) {
    /**
     * Payment method labels keyed by stored value.
     *
     * @return array<string, string>
     */
    function payment_method_options(): array
    {
        return [
            'cash' => 'Cash',
            'gcash' => 'GCash',
            'maya' => 'Maya',
            'other' => 'Other',
        ];
    }
}

if (! function_exists('payment_method_label')) {
    function payment_method_label(?string $method, ?string $custom = null): string
    {
        if ($method === 'other' && $custom) {
            return $custom;
        }

        return payment_method_options()[$method] ?? ucfirst((string) $method);
    }
}

if (! function_exists('enabled_payment_methods')) {
    /**
     * Enabled payment method values from settings, always including cash.
     *
     * @return list<string>
     */
    function enabled_payment_methods(): array
    {
        $raw = setting('payment_methods', json_encode(['cash', 'gcash', 'maya', 'other']));
        $methods = is_array($raw) ? $raw : json_decode((string) $raw, true);
        $methods = array_values(array_intersect(array_keys(payment_method_options()), (array) $methods));

        return $methods ?: ['cash'];
    }
}
