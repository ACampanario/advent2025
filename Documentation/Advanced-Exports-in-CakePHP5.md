# Advanced Exports in CakePHP5: Styled Excel, CSV, and Real-Time Charts

### 1.- Run migrations

Run migration to create a table named "sales" with 10000 rows

### 2.- Add with composer the library https://github.com/PHPOffice/PhpSpreadsheet

```
composer require "phpoffice/phpspreadsheet"
```

### 2.- Create the logic in an export function to query the data and generate an XLS or CSV file

for example in src/Controller/PagesController.php

and add to config/routes.php

```
$builder->connect('/export', ['controller' => 'Pages', 'action' => 'export']);
```

```

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
use App\Lib\BlockFileCache;

...
...
...

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
```

### 3.- Create the logic for download file by his name

for example in src/Controller/PagesController.php

and add to config/routes.php

```
$builder->connect('/download', ['controller' => 'Pages', 'action' => 'download']);
```

```
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
```

### 4.- Create a class to use as cache

This class is based on Psr\SimpleCache\CacheInterface

Is used to write a file on disk in a minimum blockSize of 100

for example in src/Lib/BlockFileCache.php

```
namespace App\Lib;

use Psr\SimpleCache\CacheInterface;

class BlockFileCache implements CacheInterface
{
    protected string $cacheDir;
    protected int $blockSize;

    public function __construct(string $cacheDir, int $blockSize = 100)
    {
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->blockSize = $blockSize;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    protected function getPath(string $key): string
    {
        return $this->cacheDir . md5($key) . '.cache';
    }

    public function get($key, $default = null): mixed
    {
        $file = $this->getPath($key);
        if (!file_exists($file)) {
            return $default;
        }
        $data = file_get_contents($file);
        return $data !== false ? unserialize($data) : $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $file = $this->getPath($key);
        return file_put_contents($file, serialize($value)) !== false;
    }

    public function delete($key): bool
    {
        $file = $this->getPath($key);
        if (file_exists($file)) {
            unlink($file);
        }
        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }
        return $results;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has($key): bool
    {
        return file_exists($this->getPath($key));
    }
}
```

# Functionality on the Front End of the Application

- There are several calls to the `export` method to:
    - Export all
    - Export to CSV
    - Export applying a filter sent in the URL
- When clicking on a link, a request is made to generate the file in a temporary directory and return information about the process.
- Once the information is displayed on the right side, the file download is triggered.

The generated Excel sheet contains custom styles and a real-time chart calculated during the export using the functions provided by **phpoffice/phpspreadsheet**.

![result](result.png)

The displayed information relates to memory usage and execution time. Cache handling can be delegated and therefore separated from the business logic without issues.

For a large amount of data, it is necessary to use a cache implementation to reduce memory consumption. However, depending on the implementation, the processing time increases — for example, in this case, when using disk-based cache.

![result](result.png)


