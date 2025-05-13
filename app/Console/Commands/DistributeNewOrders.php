<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderDistributionService;

class DistributeNewOrders extends Command
{
    protected $signature = 'orders:distribute';
    protected $description = 'Distribute new orders to staff members based on distribution settings';

    protected $orderDistributionService;

    public function __construct(OrderDistributionService $orderDistributionService)
    {
        parent::__construct();
        $this->orderDistributionService = $orderDistributionService;
    }

    public function handle()
    {
        // Get all new orders without assigned staff
        $newOrders = Order::whereNull('user_id')
            ->where('status', Order::STATUS_MOI)
            ->get();

        if ($newOrders->isEmpty()) {
            $this->info('No new orders to distribute.');
            return;
        }

        // Get all active staff members with the appropriate role/permission
        $staffMembers = User::role('staff')
            ->where('status', 'active')
            ->get();

        if ($staffMembers->isEmpty()) {
            $this->error('No active staff members found to distribute orders to.');
            return;
        }

        $orderIds = $newOrders->pluck('id')->toArray();
        $staffIds = $staffMembers->pluck('id')->toArray();

        // Distribute orders
        $result = $this->orderDistributionService->distributeOrders($orderIds, $staffIds);

        if ($result) {
            $this->info('Successfully distributed ' . count($orderIds) . ' orders to ' . count($staffIds) . ' staff members.');
        } else {
            $this->error('Failed to distribute orders.');
        }
    }
}
