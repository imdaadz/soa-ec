<?php

use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Model\Product::class, 50)->create()->each(function ($user) {
        	$user->supplier()->save(factory(App\Model\SupplierProduct::class)->make());
    	});
    }
}




