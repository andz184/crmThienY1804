<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Trait for authorization
use App\Helpers\LogHelper;

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
        // Determine which settings the user is allowed to update based on permissions
        $allowedUpdates = [];
        if (auth()->user()->can('settings.manage_favicon')) {
            $allowedUpdates['favicon'] = ['nullable', File::image()->max(1024)]; // 1MB Max, Image only
            $allowedUpdates['app_name'] = ['required', 'string', 'max:255']; // Example: Site Name
        }
        if (auth()->user()->can('settings.manage_seo')) {
            $allowedUpdates['seo_meta_title'] = ['nullable', 'string', 'max:255'];
            $allowedUpdates['seo_meta_description'] = ['nullable', 'string', 'max:1000'];
        }

        // Authorize the general update action - requires 'settings.update' permission
        $this->authorize('settings.update');

        $validated = $request->validate($allowedUpdates);

        try {
            foreach ($validated as $key => $value) {
                // Double-check specific permission for the key being updated
                if (($key === 'favicon' || $key === 'app_name') && !auth()->user()->can('settings.manage_favicon')) {
                    continue; // Skip if no permission for favicon/app_name
                }
                if (($key === 'seo_meta_title' || $key === 'seo_meta_description') && !auth()->user()->can('settings.manage_seo')) {
                    continue; // Skip if no permission for seo
                }

                // Handle File Upload (Favicon)
                if ($key === 'favicon' && $request->hasFile($key) && $request->file($key)->isValid()) {
                    $currentPath = Setting::getValue('favicon_path');
                    if ($currentPath) {
                        Storage::disk('public')->delete($currentPath);
                    }
                    $path = $request->file('favicon')->store('logos', 'public');
                    Setting::setValue('favicon_path', $path); // Store path separately
                    Setting::setValue('favicon_url', Storage::disk('public')->url($path)); // Store URL for easy access
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
}
