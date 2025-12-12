class ApexChartsRenderer {
    constructor(data, containerId) {
        this.data = data;
        this.containerId = containerId;
    }

    render() {
        new ApexCharts(document.querySelector(`#${this.containerId}`), {
            chart: { type: 'bar', height: 350 },
            series: [{ name: 'Quantity', data: this.data.series }],
            xaxis: { categories: this.data.labels },
            plotOptions: { bar: { distributed: true } },
            title: { text: 'Sales by Product', align: 'center' }
        }).render();
    }
}

window.ApexChartsRenderer = ApexChartsRenderer;
