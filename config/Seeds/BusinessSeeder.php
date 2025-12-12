<?php
declare(strict_types=1);

use Migrations\AbstractSeed;
use Faker\Factory as Faker;

class BusinessSeeder extends AbstractSeed
{
    public function run(): void
    {
        $faker = Faker::create();
        $data = [];

        $products = ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'];

        for ($i = 0; $i < 50; $i++) {
            $data[] = [
                'product' => $faker->randomElement($products),
                'quantity' => $faker->numberBetween(1, 20),
                'date' => $faker->date(),
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ];
        }

        $table = $this->table('business');
        $table->insert($data)->save();
    }
}
