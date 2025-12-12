class ChartJsRenderer {
    constructor(data, containerId) {
        this.data = data;
        this.containerId = containerId;
    }

    render() {
        const canvas = document.getElementById(this.containerId);
        if(!canvas || canvas.tagName.toLowerCase() !== 'canvas') {
            console.error('The container must be a canvas for Chart.js');
            return;
        }
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: this.data,
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true },
                    title: { display: true, text: 'Sales by Product' }
                },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
}

window.ChartJsRenderer = ChartJsRenderer;
