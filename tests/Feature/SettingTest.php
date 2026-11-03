<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_preserves_boolean_and_numeric_types(): void
    {
        // Set string
        Setting::set('bimbel_name', 'Bimbel Zigmath');
        $this->assertEquals('Bimbel Zigmath', Setting::get('bimbel_name'));

        // Set boolean toggle
        Setting::set('invoice_show_logo', true);
        $this->assertTrue(Setting::get('invoice_show_logo'));

        Setting::set('invoice_show_logo', false);
        $this->assertFalse(Setting::get('invoice_show_logo'));

        // Set numeric
        Setting::set('late_fee_value', 15000);
        $this->assertEquals(15000, Setting::get('late_fee_value'));

        // Delete setting
        $deleted = Setting::forget('bimbel_name');
        $this->assertTrue($deleted);
        $this->assertNull(Setting::get('bimbel_name'));
        $this->assertEquals('Default Name', Setting::get('bimbel_name', 'Default Name'));
    }

    public function test_get_invoice_prefix_helper(): void
    {
        Setting::set('invoice_prefix', 'INV/TEST/');
        $this->assertEquals('INV/TEST/', Setting::getInvoicePrefix());
    }
}

