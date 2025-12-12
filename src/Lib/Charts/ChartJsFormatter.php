<?php
declare(strict_types=1);

namespace App\Lib\Charts;

class ChartJsFormatter implements ChartFormatterInterface
{
    public function format(array $sales): array
    {
        $labels = [];
        $data = [];
        foreach ($sales as $v) {
            $labels[] = $v['product'];
            $data[] = (int)$v['total'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Quantity Sold',
                    'data' => $data,
                    'backgroundColor' => ['rgba(255,99,132,0.2)','rgba(54,162,235,0.2)','rgba(255,206,86,0.2)'],
                    'borderColor' => ['rgba(255,99,132,1)','rgba(54,162,235,1)','rgba(255,206,86,1)'],
                    'borderWidth' => 1
                ]
            ]
        ];
    }
}
