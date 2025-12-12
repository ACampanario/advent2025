<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateBusiness extends BaseMigration
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
        $table = $this->table('business');
        $table->addColumn('product', 'string', ['limit' => 100])
            ->addColumn('quantity', 'integer')
            ->addColumn('date', 'date')
            ->addColumn('created', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('modified', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->create();
    }
}
