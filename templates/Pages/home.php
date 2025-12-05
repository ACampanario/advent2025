<style>
    ul {
        list-style: none;
        padding-left: 0;
        margin: 0;
    }

    ul li {
        padding-left: 30px;
    }

    .loading-spinner {
        display: inline-block;
        margin-right: 8px;
        width: 14px;
        height: 14px;
        border: 2px solid #999;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        visibility: hidden;
        vertical-align: middle;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

</style>

<h3>Without Cache (10000 rows)</h3>
<div class="row">
    <ul class="col-md-6" style="min-height: 120px;">
        <li><span class="loading-spinner"></span><a href="/export">Export all to XLS</a></li>
        <li><span class="loading-spinner"></span><a href="/export?format=csv">Export all to CSV</a></li>
        <li><span class="loading-spinner"></span><a href="/export?quantity=5">Export filtering by quantity</a></li>
    </ul>
    <div id="logWithoutCache" class="log col-md-6 p-2" style="min-height: 120px;"></div>
</div>

<h3>With Cache (10000 rows)</h3>
<div class="row">
    <ul class="col-md-6" style="min-height: 120px;">
        <li><span class="loading-spinner"></span><a href="/export?cache=1">Export all to XLS</a></li>
        <li><span class="loading-spinner"></span><a href="/export?cache=1&format=csv">Export all to CSV</a></li>
        <li><span class="loading-spinner"></span><a href="/export?cache=1&quantity=5">Export filtering by quantity</a></li>
    </ul>
    <div id="logWithCache" class="log col-md-6 p-2" style="min-height: 120px;"></div>
</div>

<script>
    document.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', function(e){
            e.preventDefault();

            const li = this.closest('li');
            const spinner = li.querySelector('.loading-spinner');
            spinner.style.visibility = 'visible';

            const row = this.closest('.row');
            if (row) {
                const logDiv = row.querySelector('.log');
                if (logDiv) {
                    logDiv.innerHTML = '';
                }
            }

            const url = this.href;
            const logDiv = this.closest('ul').nextElementSibling;
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    logDiv.innerHTML = `
                    Export finished. Cache: ${data.cache} <br>
                    Memory usage: ${data.memory} MB <br>
                    Peak memory: ${data.peakMemory} MB <br>
                    Execution time: ${data.time} s <br>
                    ${data.cacheFiles ? 'Cache files: ' + data.cacheFiles : ''}
                `;
                    const link = document.createElement('a');
                    link.href = '/download?filename=' + data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    setTimeout(function(){
                        spinner.style.visibility = 'hidden';
                    }, 2000)
                });
        });
    });
</script>
