<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'full_name',
        'phone_number',
        'products',
        'status',
        'address_state',
        'address_city',
        'address_neighborhood',
        'address_street',
    ];

    protected $casts = [
        'products' => 'array',
    ];
}
