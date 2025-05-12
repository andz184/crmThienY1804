<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::setValue('app_name', 'CRM Xcelbot');
        Setting::setValue('favicon_path', null);
        Setting::setValue('favicon_url', null);
        Setting::setValue('seo_meta_title', 'CRM Xcelbot - Quản lý khách hàng');
        Setting::setValue('seo_meta_description', 'Phần mềm quản lý khách hàng, bán hàng, chăm sóc khách hàng cho doanh nghiệp.');
    }
} 