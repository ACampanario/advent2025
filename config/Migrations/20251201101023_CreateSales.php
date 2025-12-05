<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateSales extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('sales');
        $table
            ->addColumn('order_number', 'string', ['limit' => 20])
            ->addColumn('customer_name', 'string', ['limit' => 50])
            ->addColumn('product', 'string', ['limit' => 50])
            ->addColumn('quantity', 'integer')
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->create();

        $faker = \Faker\Factory::create();

        $rows = [];
        for ($i = 0; $i < 10000; $i++) {
            $rows[] = [
                'order_number' => 'ORD' . str_pad((string)($i + 1), 5, '0', STR_PAD_LEFT),
                'customer_name' => $faker->name(),
                'product' => $faker->word(),
                'quantity' => rand(1, 5),
                'price' => $faker->randomFloat(2, 10, 500),
                'created' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
            ];
        }

        $this->table('sales')->insert($rows)->save();
    }
}
