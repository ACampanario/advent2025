<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class SalesService
{
    protected Table $salesTable;
    protected array $options = [];

    public function __construct(array $params)
    {
        $this->salesTable = TableRegistry::getTableLocator()->get('Sales');
        $this->options = $params;
    }

    /**
     * Retrieves sales grouped by product
     * The data is already cached by the finder
     *
     * @return array [['product' => 'Product A', 'total' => 10], ...]
     */
    public function getSales(): array
    {
        /** @uses \App\Model\Table\SalesTable::findSales() */
        return $this->salesTable->find('sales', $this->options)->toArray();
    }
}
