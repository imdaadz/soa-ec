<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */


use Faker\Generator as Faker;

$factory->define(App\Model\OrderOut::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'created_at' => $faker->dateTimeBetween($startDate = '-30 days', $endDate = 'now')
    ];
});
