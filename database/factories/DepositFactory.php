<?php

use Faker\Generator as Faker;

$factory->define(\App\Deposit::class, function (Faker $faker) {
    return [
        'payment_id' => $faker->sha256,
        'amount' => $faker->randomNumber(2),
        'time_received' => $faker->dateTime,
        'tx_id' => $faker->sha256,
        'block_received' => $faker->randomNumber(),
    ];
});
