<?php

namespace App\Ai\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListProducts implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return <<<'DESC'
Reads the real product catalog from the restaurant database (names, descriptions, prices).

You MUST call this tool in the same assistant turn when the customer asks for: menu, carta, catálogo, lista de productos, precios, what you sell, what is available to eat, options, burgers, "dame el menú", "quiero ver qué hay", or similar—including vague hunger ("quiero comer") if they need to see what you offer.

Never invent dishes or prices: if you need concrete products or amounts, call ListProducts first, then answer using that data (you may summarize warmly for WhatsApp).
DESC;
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $products = Product::all()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
            ];
        });

        return json_encode($products, JSON_PRETTY_PRINT);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
