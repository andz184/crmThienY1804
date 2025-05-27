<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveSessionReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'report_type', // daily, monthly, yearly
        'report_data', // JSON data containing the pre-calculated report
        'last_calculated_at'
    ];

    protected $casts = [
        'report_date' => 'date',
        'report_data' => 'array',
        'last_calculated_at' => 'datetime'
    ];

    /**
     * Get report data for a specific date and type
     */
    public static function getReport(string $date, string $type = 'daily')
    {
        return self::where('report_date', $date)
            ->where('report_type', $type)
            ->first();
    }

    /**
     * Calculate and store report data for a specific date
     */
    public static function calculateAndStore(string $date, string $type = 'daily')
    {
        // Get orders for the date
        $query = Order::query()
            ->whereDate('created_at', $date)
            ->with(['pancakeOrderStatus']);

        // Calculate revenue data based on Pancake statuses
        $reportData = [
            'total_orders' => $query->count(),
            'total_revenue' => 0,
            'status_breakdown' => [],
            'status_revenue' => []
        ];

        // Get all possible statuses
        $allStatuses = PancakeOrderStatus::where('active', true)->get();

        foreach ($allStatuses as $status) {
            $ordersWithStatus = $query->clone()
                ->where('pancake_status', $status->status_code);

            $statusRevenue = $ordersWithStatus->sum('total_value');
            $statusCount = $ordersWithStatus->count();

            if ($statusCount > 0) {
                $reportData['status_breakdown'][$status->status_code] = [
                    'name' => $status->name,
                    'count' => $statusCount,
                    'color' => $status->color
                ];

                $reportData['status_revenue'][$status->status_code] = [
                    'name' => $status->name,
                    'revenue' => $statusRevenue,
                    'color' => $status->color
                ];

                // Add to total revenue if status indicates completed transaction
                if (in_array($status->api_name, ['delivered', 'done', 'completed'])) {
                    $reportData['total_revenue'] += $statusRevenue;
                }
            }
        }

        // Store or update report
        return self::updateOrCreate(
            [
                'report_date' => $date,
                'report_type' => $type
            ],
            [
                'report_data' => $reportData,
                'last_calculated_at' => now()
            ]
        );
    }
}
