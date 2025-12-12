<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class BusinessService
{
    protected Table $businessTable;

    public function __construct()
    {
        $this->businessTable = TableRegistry::getTableLocator()->get('Business');
    }

    /**
     * Retrieves sales grouped by product
     * The data is already cached by the finder
     *
     * @return array [['product' => 'Product A', 'total' => 10], ...]
     */
    public function getSalesByProduct(): array
    {
        return $this->businessTable->find('salesByProduct')
            ->enableHydration(false)
            ->toArray();
    }
}
