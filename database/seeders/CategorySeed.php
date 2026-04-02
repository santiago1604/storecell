<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeed extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Cables',
            'Cargadores',
            'Luces LED',
            'Parlantes',
            'Audífonos Cable',
            'Audífonos Bluetooth',
            'Forros Silicona',
            'Forros Motomo',
            'Forros Armadura',
            'Vidrios Templados',
            'Accesorios Varios',
        ];
        foreach ($categories as $cat) {
            DB::table('categories')->updateOrInsert([
                'name' => $cat
            ]);
        }
    }
}
