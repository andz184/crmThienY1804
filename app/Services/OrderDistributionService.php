<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use App\Models\User;
use App\Models\Order;

class OrderDistributionService
{
    /**
     * Distribute orders to users based on the configured pattern
     *
     * @param array $orderIds Array of order IDs to distribute
     * @param array $userIds Array of user IDs to distribute orders to
     * @return bool
     */
    public function distributeOrders(array $orderIds, array $userIds)
    {
        if (empty($orderIds) || empty($userIds)) {
            return false;
        }

        $settings = WebsiteSetting::getOrderDistributionSettings();
        $pattern = array_map('intval', explode(',', $settings['pattern']));
        $type = $settings['type'];

        if ($type === 'sequential') {
            return $this->distributeSequentially($orderIds, $userIds);
        } else {
            return $this->distributeInBatches($orderIds, $userIds, $pattern);
        }
    }

    /**
     * Distribute orders sequentially (1,2,3 pattern)
     *
     * @param array $orderIds
     * @param array $userIds
     * @return bool
     */
    protected function distributeSequentially(array $orderIds, array $userIds)
    {
        $totalUsers = count($userIds);
        $currentUserIndex = 0;

        foreach ($orderIds as $orderId) {
            $userId = $userIds[$currentUserIndex];

            Order::where('id', $orderId)->update(['user_id' => $userId]);

            $currentUserIndex = ($currentUserIndex + 1) % $totalUsers;
        }

        return true;
    }

    /**
     * Distribute orders in batches (33,1,33,1 pattern)
     *
     * @param array $orderIds
     * @param array $userIds
     * @param array $pattern
     * @return bool
     */
    protected function distributeInBatches(array $orderIds, array $userIds, array $pattern)
    {
        $totalOrders = count($orderIds);
        $currentOrder = 0;
        $patternIndex = 0;
        $userIndex = 0;

        while ($currentOrder < $totalOrders) {
            $batchSize = min($pattern[$patternIndex], $totalOrders - $currentOrder);
            $userId = $userIds[$userIndex];

            // Update batch of orders
            $batchOrderIds = array_slice($orderIds, $currentOrder, $batchSize);
            Order::whereIn('id', $batchOrderIds)->update(['user_id' => $userId]);

            $currentOrder += $batchSize;
            $patternIndex = ($patternIndex + 1) % count($pattern);
            $userIndex = ($userIndex + 1) % count($userIds);
        }

        return true;
    }

    /**
     * Validate distribution pattern
     *
     * @param string $pattern
     * @return bool
     */
    public function validatePattern($pattern)
    {
        // Pattern should be comma-separated numbers
        if (!preg_match('/^\d+(,\d+)*$/', $pattern)) {
            return false;
        }

        $numbers = array_map('intval', explode(',', $pattern));

        // All numbers should be positive
        foreach ($numbers as $number) {
            if ($number <= 0) {
                return false;
            }
        }

        return true;
    }
}
