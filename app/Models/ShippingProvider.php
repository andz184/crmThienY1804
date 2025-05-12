<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'pancake_id',
        'name',
        'code',
        'description',
        'is_active',
        'pancake_partner_id',
    ];
}
