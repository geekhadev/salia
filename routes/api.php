<?php

use App\Http\Controllers\WhatsAppWebhookController;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/healthcheck', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::get('/products', function () {
    return response()->json(Product::all());
});

Route::get('/orders', function () {
    return response()->json(Order::all());
});

Route::post('/orders', function (Request $request) {
    $validated = $request->validate([
        'full_name' => 'required|string|max:255',
        'phone_number' => 'required|string|max:20',
        'products' => 'required|array',
        'address_state' => 'required|string|max:255',
        'address_city' => 'required|string|max:255',
        'address_neighborhood' => 'required|string|max:255',
        'address_street' => 'required|string|max:255',
    ]);

    $order = Order::create($validated);

    return response()->json($order, 201);
});

Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle']);
