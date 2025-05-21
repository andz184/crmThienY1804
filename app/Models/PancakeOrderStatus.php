<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PancakeOrderStatus extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status_code',
        'name',
        'api_name',
        'color',
        'description',
        'active',
    ];
    
    /**
     * Get status name by code
     *
     * @param int $statusCode
     * @return string|null
     */
    public static function getNameByCode($statusCode)
    {
        $status = self::where('status_code', $statusCode)->first();
        return $status ? $status->name : null;
    }
    
    /**
     * Get color for status by code
     *
     * @param int $statusCode
     * @return string
     */
    public static function getColorByCode($statusCode)
    {
        $status = self::where('status_code', $statusCode)->first();
        return $status && $status->color ? $status->color : 'secondary';
    }
    
    /**
     * Get Bootstrap badge class for status
     *
     * @param int $statusCode
     * @return string
     */
    public static function getBadgeClassByCode($statusCode)
    {
        $color = self::getColorByCode($statusCode);
        return 'badge-' . $color;
    }
}
