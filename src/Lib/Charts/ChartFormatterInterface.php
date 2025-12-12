<?php
declare(strict_types=1);

namespace App\Lib\Charts;

interface ChartFormatterInterface
{
    /**
     * Build the data for the specific library
     * @param array $sales
     * @return array
     */
    public function format(array $sales): array;
}
