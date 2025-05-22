<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Trait for authorization
use App\Helpers\LogHelper;
use App\Models\User;
use App\Models\Order;
use App\Models\WebsiteSetting;

class SettingsController extends Controller
{
    use AuthorizesRequests; // Use the trait

    /**
     * Display the settings page.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        // Removed authorization check here as per previous discussion
        // $this->authorize('settings.view');

        // Fetch all settings, keyed by their key for easy access in the view
        $settings = Setting::pluck('value', 'key')->all();

        // Return the view for the settings page, passing the settings data
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update the specified settings in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        // Ensure user is authorized for the basic update operation
        $this->authorize('settings.update');
        
        // Define basic allowed updates
        $allowedUpdates = [
            'app_name' => ['required', 'string', 'max:255'],
            'favicon' => ['nullable', File::image()->max(1024)], // 1MB Max, Image only
            'seo_meta_title' => ['nullable', 'string', 'max:255'],
            'seo_meta_description' => ['nullable', 'string', 'max:1000'],
        ];

        $validated = $request->validate($allowedUpdates);

        try {
            foreach ($validated as $key => $value) {
                // Handle File Upload (Favicon)
                if ($key === 'favicon' && $request->hasFile($key) && $request->file($key)->isValid()) {
                    $currentPath = Setting::getValue('favicon_path');
                    if ($currentPath) {
                        Storage::disk('public')->delete($currentPath);
                    }
                    $path = $request->file('favicon')->store('logos', 'public');
                    Setting::setValue('favicon_path', $path); // Store path separately
                    Setting::setValue('favicon_url', asset('storage/' . $path)); // Store URL for easy access
                }
                // Handle Text-based settings (excluding the file input itself)
                elseif ($key !== 'favicon') {
                    Setting::setValue($key, $value);
                }
            }

            // Clear relevant cache after updating settings (e.g., config if app_name changed)
            Artisan::call('config:cache');

            LogHelper::log('update_settings', null, null, $request->all());

            return redirect()->route('admin.settings.index')->with('success', 'Cài đặt đã được cập nhật.');

        } catch (\Exception $e) {
            Log::error("Error updating settings: " . $e->getMessage());
            return redirect()->route('admin.settings.index')->with('error', 'Lỗi cập nhật cài đặt. Vui lòng kiểm tra logs.');
        }
    }

    /**
     * Clear specified application caches.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearCache(Request $request)
    {
         $this->authorize('settings.clear_cache');

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            LogHelper::log('clear_cache', null, null, null);

            return redirect()->route('admin.settings.index')->with('success', 'Đã xóa cache thành công.');
        } catch (\Exception $e) {
            Log::error("Error clearing cache: " . $e->getMessage());
            return redirect()->route('admin.settings.index')->with('error', 'Lỗi xóa cache. Vui lòng kiểm tra logs.');
        }
    }

    /**
     * Display the order distribution settings page.
     */
    public function orderDistribution()
    {
        $this->authorize('settings.manage');

        // Get staff statistics - now using pancake_uuid to connect with assigning_seller_id
        $staffStats = User::role('staff')
            ->select('users.id', 'users.name', 'users.pancake_uuid')
            ->selectRaw('COUNT(orders.id) as total_orders_count')
            ->selectRaw('COUNT(CASE WHEN orders.status IN (?, ?, ?) THEN 1 END) as processing_orders_count', 
                [\App\Models\Order::STATUS_MOI, \App\Models\Order::STATUS_CAN_XU_LY, \App\Models\Order::STATUS_CHO_HANG])
            ->leftJoin('orders', 'users.pancake_uuid', '=', 'orders.assigning_seller_id')
            ->groupBy('users.id', 'users.name', 'users.pancake_uuid')
            ->get();
            
        // Get latest skipped staff sync reasons from cache
        $skippedStaffReasons = \Illuminate\Support\Facades\Cache::get('pancake_sync_skipped_staff', []);
        
        // Get order distribution settings
        $settings = [
            'order_distribution_type' => \App\Models\WebsiteSetting::get('order_distribution_type', 'sequential'),
            'order_distribution_pattern' => \App\Models\WebsiteSetting::get('order_distribution_pattern', '1,1,1')
        ];

        return view('admin.settings.order_distribution', compact(
            'settings', 
            'staffStats',
            'skippedStaffReasons'
        ));
    }

    /**
     * Update the order distribution settings.
     */
    public function updateOrderDistribution(Request $request)
    {
        $this->authorize('settings.manage');

        $validated = $request->validate([
            'order_distribution_type' => ['required', 'string', Rule::in(['sequential', 'batch'])],
            'order_distribution_pattern' => ['required', 'string', 'regex:/^[0-9,]+$/'],
        ]);

        WebsiteSetting::set('order_distribution_type', $validated['order_distribution_type']);
        WebsiteSetting::set('order_distribution_pattern', $validated['order_distribution_pattern']);

        LogHelper::log('update_order_distribution_settings', null, null, $validated);

        return redirect()->route('admin.settings.order-distribution')
            ->with('success', 'Cài đặt phân phối đơn hàng đã được cập nhật.');
    }
}
