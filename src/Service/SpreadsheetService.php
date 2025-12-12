<?php
declare(strict_types=1);

namespace App\Service;

use App\Lib\BlockFileCache;
use Cake\ORM\Table;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SpreadsheetService
{
    public function __construct(bool $cache)
    {
        if ($cache) {
            $cachePath = TMP . 'cache' . DS . 'phpspreadsheet' . DS;
            $cache = new BlockFileCache($cachePath, 100);
            Settings::setCache($cache);
        }
    }

    public function generate(array $sales, $format)
    {
        // Create spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['Order Number', 'Customer Name', 'Product', 'Quantity', 'Price', 'Created'];
        $sheet->fromArray($headers, null, 'A1');

        // Header styles
        $sheet->getStyle('A1:F1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:F1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4CAF50');

        // Data with alternating rows
        $rowNum = 2;
        foreach ($sales as $sale) {
            $sheet->setCellValue("A{$rowNum}", $sale->order_number)
                ->setCellValue("B{$rowNum}", $sale->customer_name)
                ->setCellValue("C{$rowNum}", $sale->product)
                ->setCellValue("D{$rowNum}", $sale->quantity)
                ->setCellValue("E{$rowNum}", $sale->price)
                ->setCellValue("F{$rowNum}", $sale->created->format('Y-m-d H:i:s'));

            if ($rowNum % 2 === 0) {
                $sheet->getStyle("A{$rowNum}:F{$rowNum}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E8F5E9');
            }
            $rowNum++;
        }

        // Graph
        $labels = [
            new DataSeriesValues('String', "Worksheet!A2:A10001", null, 100)
        ];

        $quantityValues = new DataSeriesValues('Number', "Worksheet!D2:D101", null, 100);
        $priceValues    = new DataSeriesValues('Number', "Worksheet!E2:E101", null, 100);

        $quantityName = new DataSeriesValues('String', "Worksheet!D1", null, 1);
        $priceName    = new DataSeriesValues('String', "Worksheet!E1", null, 1);

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            [0, 1],
            [$quantityName, $priceName],
            $labels,
            [$quantityValues, $priceValues]
        );

        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $title = new Title('Quantity & Price per Order');

        $chart = new Chart(
            'Sales Chart',
            $title,
            null,
            $plotArea
        );

        $chart->setTopLeftPosition('I2');
        $chart->setBottomRightPosition('U30');

        $sheet->addChart($chart);

        // Save file
        $filename = 'sales_export_' . date('Ymd_His') . '.' . $format;
        $tempPath = TMP . $filename;
        if ($format === 'csv') {
            $writer = new Csv($spreadsheet);
        } else {
            $writer = new Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);
        }
        $writer->save($tempPath);

        return $filename;
    }
}
