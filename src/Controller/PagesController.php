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

use App\Lib\Charts\ApexChartsFormatter;
use App\Lib\Charts\ChartJsFormatter;
use App\Lib\Charts\JqPlotFormatter;
use App\Service\BusinessService;
use App\Service\SalesService;
use App\Service\SpreadsheetService;
use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;

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
        $format = $this->getRequest()->getQuery('format') ?? 'xlsx'; // 'csv' o 'xlsx'
        $filters['quantity'] = $this->getRequest()->getQuery('quantity');
        $cache = $this->getRequest()->getQuery('cache') != null;

        // Get data
        $service = new SalesService($filters);
        $sales = $service->getSales();

        // Create spreadsheet
        $service = new SpreadsheetService($cache);
        $filename = $service->generate($sales, $format);

        // Measure memory and final time
        $endTime = microtime(true);
        $endMem = memory_get_usage();
        $peakMem = memory_get_peak_usage();

        $cacheFiles = 0;
        if ($cache) {
            clearstatcache(true, TMP . 'cache/phpspreadsheet/');
            $cacheFiles = count(glob(TMP . 'cache/phpspreadsheet/*.cache'));
        }

        // Return JSON stats and filename
        $data = [
            'cache' => $cache ? 'ON' : 'OFF',
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

    public function chartData()
    {
        $type = $this->getRequest()->getQuery('type');

        $chartData = [];
        if ($type !== null) {
            $service = new BusinessService();
            $data = $service->getSalesByProduct();

            $formatter = match (strtolower($type)) {
                'apexcharts' => new ApexChartsFormatter(),
                'jqplot' => new JqPlotFormatter(),
                default => new ChartJsFormatter(),
            };
            $chartData = $formatter->format($data);
        }

        $this->set(compact('chartData', 'type'));
    }
}
