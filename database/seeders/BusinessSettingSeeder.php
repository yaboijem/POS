<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use Illuminate\Database\Seeder;

class BusinessSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'business_name' => "Aling Nena's Karinderya",
            'business_address' => '123 Mabini Street, Barangay San Roque, Quezon City',
            'business_phone' => '0917 123 4567',
            'business_email' => 'hello@alingnenas.test',
            'logo_path' => null,
            'receipt_footer' => 'Salamat po! Please come again.',
            'currency' => 'PHP',
            'timezone' => 'Asia/Manila',
            'low_stock_threshold' => '5',
            'payment_methods' => json_encode(['cash', 'gcash', 'maya', 'other']),
            'tax_enabled' => '0',
            'tax_rate' => '0',
            'tax_inclusive' => '0',
        ];

        foreach ($defaults as $key => $value) {
            BusinessSetting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
