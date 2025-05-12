<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'district_code',
    ];

    /**
     * Get the district that owns the ward.
     */
    public function district()
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }
}
