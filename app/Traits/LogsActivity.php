<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        // Log khi tạo mới
        static::created(function ($model) {
            try {
                self::logActivity('created', $model);
                Log::info('Created log for model: ' . get_class($model) . ' with ID: ' . $model->id);
            } catch (\Exception $e) {
                Log::error('Error logging creation: ' . $e->getMessage());
            }
        });

        // Log khi cập nhật
        static::updated(function ($model) {
            try {
                // Chỉ log khi có thay đổi thực sự
                $changes = $model->getDirty();
                if (!empty($changes)) {
                    self::logActivity('updated', $model);
                    Log::info('Updated log for model: ' . get_class($model) . ' with ID: ' . $model->id);
                    Log::info('Changes: ' . json_encode($changes));
                }
            } catch (\Exception $e) {
                Log::error('Error logging update: ' . $e->getMessage());
            }
        });

        // Log khi xóa
        static::deleted(function ($model) {
            try {
                self::logActivity('deleted', $model);
                Log::info('Deleted log for model: ' . get_class($model) . ' with ID: ' . $model->id);
            } catch (\Exception $e) {
                Log::error('Error logging deletion: ' . $e->getMessage());
            }
        });
    }

    protected static function logActivity($action, $model)
    {
        try {
            // Get authenticated user ID or find system user
            $userId = Auth::id();

            // If no authenticated user, try to find system user by email
            if (!$userId) {
                $systemUser = \App\Models\User::where('email', 'system@example.com')
                    ->orWhere('email', 'superadmin@example.com')
                    ->first();
                $userId = $systemUser ? $systemUser->id : null;
            }

            // If still no user found, create log without user
            $data = [
                'user_id' => $userId,
                'action' => $action,
                'module' => class_basename($model),
                'description' => self::getActivityDescription($action, $model),
                'old_data' => $action === 'updated' ? $model->getOriginal() : null,
                'new_data' => $model->getAttributes(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ];

            Log::info('Attempting to create activity log with data: ' . json_encode($data));

            $log = ActivityLog::create($data);

            Log::info('Successfully created activity log with ID: ' . $log->id);

            return $log;
        } catch (\Exception $e) {
            Log::error('Failed to create activity log: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    protected static function getActivityDescription($action, $model)
    {
        $modelName = class_basename($model);
        $userName = Auth::check() ? Auth::user()->name : 'System';

        // Chuyển đổi action sang tiếng Việt
        switch ($action) {
            case 'created':
                $actionText = 'thêm mới';
                break;
            case 'updated':
                $actionText = 'cập nhật';
                break;
            case 'deleted':
                $actionText = 'xóa';
                break;
            default:
                $actionText = $action;
        }

        // Chuyển đổi tên model sang tiếng Việt
        switch ($modelName) {
            case 'Order':
                $modelText = 'đơn hàng';
                $identifier = "#{$model->order_code}";
                break;
            case 'Customer':
                $modelText = 'khách hàng';
                $identifier = $model->name;
                break;
            case 'User':
                $modelText = 'người dùng';
                $identifier = $model->email;
                break;
            case 'Product':
                $modelText = 'sản phẩm';
                $identifier = $model->name;
                break;
            case 'CallLog':
                $modelText = 'cuộc gọi';
                $identifier = "#{$model->id}";
                break;
            default:
                $modelText = strtolower($modelName);
                $identifier = "#{$model->id}";
        }

        $description = "{$userName} đã {$actionText} {$modelText} {$identifier}";

        if ($action === 'updated') {
            $changes = $model->getDirty();
            $changedFields = collect($changes)->keys()->map(function($field) {
                // Chuyển đổi tên trường sang tiếng Việt
                switch ($field) {
                    case 'name':
                        return 'tên';
                    case 'email':
                        return 'email';
                    case 'phone':
                        return 'số điện thoại';
                    case 'address':
                        return 'địa chỉ';
                    case 'status':
                        return 'trạng thái';
                    case 'notes':
                        return 'ghi chú';
                    default:
                        return $field;
                }
            })->implode(', ');

            $description .= " (Các trường thay đổi: {$changedFields})";
        }

        return $description;
    }
}
