<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class QuickSeed extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['email'=>'admin@tienda.test'],
            ['name'=>'Admin','password'=>Hash::make('demo1234'),'role'=>'admin','created_at'=>now()]
        );
        DB::table('users')->updateOrInsert(
            ['email'=>'vendedor@tienda.test'],
            ['name'=>'Vendedor','password'=>Hash::make('demo1234'),'role'=>'seller','created_at'=>now()]
        );
    }
}
