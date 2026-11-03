<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General / Identitas Bimbel
            ['key' => 'bimbel_name', 'value' => 'Zigmath', 'type' => Setting::TYPE_STRING, 'group' => Setting::GROUP_GENERAL],
            ['key' => 'bimbel_address', 'value' => 'Jl. Pendidikan No. 123, Surabaya', 'type' => Setting::TYPE_STRING, 'group' => Setting::GROUP_GENERAL],
            ['key' => 'bimbel_phone', 'value' => '081234567890', 'type' => Setting::TYPE_STRING, 'group' => Setting::GROUP_GENERAL],
            ['key' => 'bimbel_email', 'value' => 'info@zigmath.com', 'type' => Setting::TYPE_STRING, 'group' => Setting::GROUP_GENERAL],

            // Invoice & Finance
            ['key' => 'invoice_prefix', 'value' => 'INV/ZGM/', 'type' => Setting::TYPE_STRING, 'group' => Setting::GROUP_FINANCE],
            ['key' => 'late_fee_type', 'value' => 'none', 'type' => Setting::TYPE_STRING, 'group' => Setting::GROUP_FINANCE],
            ['key' => 'late_fee_value', 'value' => 0, 'type' => Setting::TYPE_NUMBER, 'group' => Setting::GROUP_FINANCE],
            ['key' => 'invoice_show_logo', 'value' => true, 'type' => Setting::TYPE_BOOLEAN, 'group' => Setting::GROUP_FINANCE],

            // Bank Accounts
            ['key' => 'bank_name', 'value' => 'BCA', 'type' => Setting::TYPE_STRING, 'group' => Setting::GROUP_FINANCE],
            ['key' => 'bank_number', 'value' => '1234567890', 'type' => Setting::TYPE_STRING, 'group' => Setting::GROUP_FINANCE],
            ['key' => 'bank_holder', 'value' => 'Bimbel Zigmath', 'type' => Setting::TYPE_STRING, 'group' => Setting::GROUP_FINANCE],

            // System
            ['key' => 'maintenance_mode', 'value' => false, 'type' => Setting::TYPE_BOOLEAN, 'group' => Setting::GROUP_SYSTEM],
        ];

        foreach ($settings as $item) {
            Setting::set($item['key'], $item['value'], $item['type'], $item['group']);
        }

        $this->command->info('✅ Default settings berhasil diinisialisasi.');
    }
}

