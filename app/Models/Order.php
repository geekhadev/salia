<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

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
        'location',
    ];

    protected $casts = [
        'products' => 'array',
        'location' => 'array',
    ];
}
