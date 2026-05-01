<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::insert([
            [
                'name' => 'Classic Burger',
                'description' => 'Hamburguesa clásica con carne de res, lechuga, tomate, cebolla y salsa especial.',
                'price' => 8.99,
            ],
            [
                'name' => 'Cheese Burger',
                'description' => 'Hamburguesa doble queso cheddar, pepinillos y salsa de mostaza.',
                'price' => 10.99,
            ],
            [
                'name' => 'Bacon Burger',
                'description' => 'Hamburguesa con tocino crujiente, queso ahumado y salsa BBQ.',
                'price' => 12.99,
            ],
            [
                'name' => 'Chicken Burger',
                'description' => 'Hamburguesa de pollo a la parrilla con mayonesa de ajo y lechuga fresca.',
                'price' => 9.99,
            ],
            [
                'name' => 'Veggie Burger',
                'description' => 'Hamburguesa vegetariana con portobello, aguacate y vegetales frescos.',
                'price' => 10.49,
            ],
            [
                'name' => 'Papas Fritas',
                'description' => 'Porción de papas fritas crujientes con sal y especias.',
                'price' => 3.99,
            ],
            [
                'name' => 'Aros de Cebolla',
                'description' => 'Aros de cebolla empanizados y fritos hasta quedar dorados.',
                'price' => 4.99,
            ],
            [
                'name' => 'Malteada de Chocolate',
                'description' => 'Malteada cremosa de chocolate con crema batida.',
                'price' => 5.99,
            ],
            [
                'name' => 'Refresco',
                'description' => 'Refresco de 500ml. Disponible en Coca-Cola, Sprite y Fanta.',
                'price' => 2.49,
            ],
            [
                'name' => 'Combo Familiar',
                'description' => '4 hamburguesas clásicas, 2 papas grandes y 4 refrescos.',
                'price' => 39.99,
            ],
        ]);
    }
}
