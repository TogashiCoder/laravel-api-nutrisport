<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Whey Protein', 'stock' => 100, 'description' => 'Protéine whey pour récupération musculaire.', 'is_available' => true],
            ['name' => 'BCAA', 'stock' => 80, 'description' => 'Acides aminés branchés pour l\'endurance.', 'is_available' => true],
            ['name' => 'Créatine', 'stock' => 120, 'description' => 'Créatine monohydrate pour la force.', 'is_available' => true],
            ['name' => 'Vitamine D3', 'stock' => 90, 'description' => 'Complément vitamine D3.', 'is_available' => true],
            ['name' => 'Oméga 3', 'stock' => 75, 'description' => 'Acides gras essentiels.', 'is_available' => true],
            ['name' => 'Pré-workout', 'stock' => 60, 'description' => 'Booster énergie avant l\'entraînement.', 'is_available' => true],
            ['name' => 'Barre protéinée', 'stock' => 200, 'description' => 'Snack protéiné.', 'is_available' => true],
            ['name' => 'Glutamine', 'stock' => 70, 'description' => 'Récupération et immunité.', 'is_available' => true],
        ];

        foreach ($products as $product) {
            DB::table('products')->insert(array_merge($product, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
