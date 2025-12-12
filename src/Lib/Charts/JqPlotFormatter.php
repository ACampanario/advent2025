<?php
declare(strict_types=1);

namespace App\Lib\Charts;

class JqPlotFormatter implements ChartFormatterInterface
{
    public function format(array $sales): array
    {
        $chartData = [];
        foreach ($sales as $v) {
            $chartData[] = [$v['product'], (int)$v['total']];
        }

        return $chartData;
    }
}
