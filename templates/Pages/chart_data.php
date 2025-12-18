<h2><?= __('Sales Chart - {0}', ucfirst($type));?></h2>
<div class="mb-4">
    <a href="/pages/charts"><< Back</a>
</div>
<!-- Containers -->
<canvas id="chartjsContainer" style="width:100%; height:400px; display:none;"></canvas>
<div id="apexContainer" style="width:100%; height:400px; display:none;"></div>
<div id="jqplotContainer" style="width:100%; height:400px; display:none;"></div>

<!-- Required libraries -->
<?php
echo $this->Html->script([
    'https://cdn.jsdelivr.net/npm/chart.js',
    'https://cdn.jsdelivr.net/npm/apexcharts',
    'https://code.jquery.com/jquery-3.6.0.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/jquery.jqplot.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.barRenderer.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.categoryAxisRenderer.min.js'
], ['block' => true]);
?>

<script>
    const type = '<?= $type ?>';
    const chartData = <?= json_encode($chartData) ?>;

    const containerIds = {
        chartjs: 'chartjsContainer',
        apexcharts: 'apexContainer',
        jqplot: 'jqplotContainer'
    };
    document.getElementById(containerIds[type]).style.display = 'block';

    const paths = {
        chartjs: '/js/chartsRenderers/ChartJsRenderer.js',
        apexcharts: '/js/chartsRenderers/ApexChartsRenderer.js',
        jqplot: '/js/chartsRenderers/JqPlotRenderer.js'
    };

    const classes = {
        chartjs: 'ChartJsRenderer',
        apexcharts: 'ApexChartsRenderer',
        jqplot: 'JqPlotRenderer'
    };

    // Dynamically load the corresponding JS class
    if(paths[type]) {
        const script = document.createElement('script');
        script.src = paths[type];
        script.onload = () => {
            const RendererClass = window[classes[type]];
            if(RendererClass) {
                const chart = new RendererClass(chartData, containerIds[type]);
                chart.render();
            } else {
                console.error('Class not found for type:', type);
            }
        };
        document.head.appendChild(script);
    } else {
        console.error('Unsupported chart type:', type);
    }
</script>
