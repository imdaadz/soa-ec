<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use Faker\Generator as Faker;

$factory->define(App\Model\Product::class, function (Faker $faker) {
    return [
        'user_id' => 1,
		'product_name' => $faker->text($maxNbChars = 200),
		'product_sku' => $faker->word,
		'product_qty' => $faker->randomDigit(5),
		'product_price' => $faker->randomNumber(2),
		'product_desc' => $faker->paragraph($nbSentences = 2, $variableNbSentences = true),
		'product_photo' => $faker->imageUrl($width = 640, $height = 480)
    ];
});
