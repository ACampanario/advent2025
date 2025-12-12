class JqPlotRenderer {
    constructor(data, containerId) {
        this.data = data;
        this.containerId = containerId;
    }

    render() {
        $(function(){
            $.jqplot(this.containerId, [this.data], {
                seriesDefaults: {
                    renderer: $.jqplot.BarRenderer,
                    rendererOptions: { varyBarColor: true },
                    pointLabels: { show: true }
                },
                axes: {
                    xaxis: { renderer: $.jqplot.CategoryAxisRenderer },
                    yaxis: { min: 0 }
                }
            });
        }.bind(this));
    }
}

window.JqPlotRenderer = JqPlotRenderer;
