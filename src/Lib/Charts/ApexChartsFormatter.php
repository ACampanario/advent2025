<?php
declare(strict_types=1);

namespace App\Lib\Charts;

class ApexChartsFormatter implements ChartFormatterInterface
{
    public function format(array $sales): array
    {
        $labels = [];
        $series = [];
        foreach ($sales as $v) {
            $labels[] = $v['product'];
            $series[] = (int)$v['total'];
        }

        return [
            'labels' => $labels,
            'series' => $series
        ];
    }
}
