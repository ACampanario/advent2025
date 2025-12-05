<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use App\Lib\BlockFileCache;
use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Settings;
use App\Lib\SpreadsheetCache;


/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/5/en/controllers/pages-controller.html
 */
class PagesController extends AppController
{
    /**
     * Displays a view
     *
     * @param string ...$path Path segments.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\ForbiddenException When a directory traversal attempt.
     * @throws \Cake\View\Exception\MissingTemplateException When the view file could not
     *   be found and in debug mode.
     * @throws \Cake\Http\Exception\NotFoundException When the view file could not
     *   be found and not in debug mode.
     * @throws \Cake\View\Exception\MissingTemplateException In debug mode.
     */
    public function display(string ...$path): ?Response
    {
        if (!$path) {
            return $this->redirect('/');
        }
        if (in_array('..', $path, true) || in_array('.', $path, true)) {
            throw new ForbiddenException();
        }
        $page = $subpage = null;

        if (!empty($path[0])) {
            $page = $path[0];
        }
        if (!empty($path[1])) {
            $subpage = $path[1];
        }
        $this->set(compact('page', 'subpage'));

        try {
            return $this->render(implode('/', $path));
        } catch (MissingTemplateException $exception) {
            if (Configure::read('debug')) {
                throw $exception;
            }
            throw new NotFoundException();
        }
    }
    public function export(): Response
    {
        // Measure start time and memory
        $startTime = microtime(true);

        // Query params
        $format = $this->request->getQuery('format') ?? 'xlsx'; // 'csv' o 'xlsx'
        $quantityFilter = $this->request->getQuery('quantity');
        $cacheParam = $this->request->getQuery('cache');

        // Using cache
        if ($cacheParam !== null) {
            $cachePath = TMP . 'cache' . DS . 'phpspreadsheet' . DS;
            $cache = new BlockFileCache($cachePath, 100);
            Settings::setCache($cache);
        }

        // Get data
        $query = $this->fetchTable('Sales')->find('all')->orderBy(['Sales.id' => 'ASC']);
        if ($quantityFilter !== null) {
            $query->where(['Sales.quantity' => (int)$quantityFilter]);
        }
        $sales = $query->toArray();

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

        // Graph Quantity vs Price
        $totalQuantity = array_sum(array_map(fn($s) => $s->quantity, $sales));
        $totalPrice = array_sum(array_map(fn($s) => $s->price, $sales));

        $labels = [new DataSeriesValues('String', null, null, 2, ['Total Quantity', 'Total Price'])];
        $values = [new DataSeriesValues('Number', null, null, 2, [$totalQuantity, $totalPrice])];

        $series = new DataSeries(
            DataSeries::TYPE_PIECHART,
            null,
            range(0, count($values) - 1),
            $labels,
            [],
            $values
        );

        $plotArea = new PlotArea(null, [$series]);
        $chart = new Chart('Sales Overview', new Title('Quantity vs Price'), null, $plotArea);
        $chart->setTopLeftPosition('K2');
        $chart->setBottomRightPosition('O17');
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

        // Measure memory and final time
        $endTime = microtime(true);
        $endMem = memory_get_usage();
        $peakMem = memory_get_peak_usage();

        $cacheFiles = 0;
        if ($cacheParam !== null) {
            clearstatcache(true, TMP . 'cache/phpspreadsheet/');
            $cacheFiles = count(glob(TMP . 'cache/phpspreadsheet/*.cache'));
        }

        // Return JSON stats and filename
        $data = [
            'cache' => $cacheParam ? 'ON' : 'OFF',
            'memory' => round($endMem/1024/1024,2),
            'peakMemory' => round($peakMem/1024/1024,2),
            'time' => round($endTime-$startTime,2),
            'cacheFiles' => $cacheFiles,
            'filename' => $filename
        ];

        $this->response = $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($data));

        return $this->response;
    }

    public function download()
    {
        $filename = $this->getRequest()->getQuery('filename');
        $tempPath = TMP . $filename;
        if (!file_exists($tempPath)) {
            throw new NotFoundException("File not found");
        }

        $ext = pathinfo($tempPath, PATHINFO_EXTENSION);
        $type = $ext === 'csv'
            ? 'text/csv'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return $this->response
            ->withType($type)
            ->withDownload($filename)
            ->withFile($tempPath);
    }
}
