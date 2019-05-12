<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Model;
use Faker\Generator as Faker;

$factory->define(App\Model\OrderOutDetail::class, function (Faker $faker) {
    return [
        'product_id' => $faker->biasedNumberBetween($min = 1, $max = 50),
        'order_out_id' => $faker->biasedNumberBetween($min = 1, $max = 30),
        'total_qty' => $faker->randomDigitNotNull
    ];
});
