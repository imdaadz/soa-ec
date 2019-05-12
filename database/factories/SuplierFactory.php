<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use Faker\Generator as Faker;

$factory->define(App\Model\SupplierProduct::class, function (Faker $faker) {
    return [
         'supplier_id' => $faker->biasedNumberBetween($min = 1, $max = 3),
    ];
});
