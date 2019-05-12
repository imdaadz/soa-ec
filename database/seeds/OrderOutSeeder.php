<?php

use Illuminate\Database\Seeder;

class OrderOutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		factory(App\Model\OrderOut::class, 30)->create()->each(function ($user) {
			for ($i=0; $i < rand(5,10); $i++) { 
				$user->detail()->save(factory(App\Model\OrderOutDetail::class)->make());
			}
		});;
    }
}
