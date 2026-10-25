// Analytics charts initializer (uses Chart.js, loaded conditionally via $load_charts)
// Expected markup:
//   <script type="application/json" id="chartId">{...chart.js config...}</script>
//   <canvas data-chart data-chart-data="chartId"></canvas>
document.addEventListener('DOMContentLoaded', function() {
    if (window.Chart === undefined) return;

    var canvases = document.querySelectorAll('canvas[data-chart]');
    canvases.forEach(function(canvas) {
        var dataEl = document.getElementById(canvas.getAttribute('data-chart-data'));
        if (!dataEl) return;

        var config;
        try {
            config = JSON.parse(dataEl.textContent);
        } catch (e) {
            return;
        }

        if (!config || !config.data || !canvas.getContext) return;

        var type = config.type || 'line';
        config.options = mergeDefaults(pbmChartDefaults(type), config.options || {});

        new Chart(canvas.getContext('2d'), config);
    });
});

function pbmChartDefaults(type) {
    var defaults = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                position: 'bottom',
                labels: { usePointStyle: true, boxWidth: 8, padding: 16 }
            },
            tooltip: {
                callbacks: {
                    label: function(item) {
                        var raw = item.parsed !== undefined
                            ? (item.parsed.y !== undefined ? item.parsed.y : item.parsed)
                            : item.raw;
                        var val = Number(raw).toLocaleString('en-IN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        return (item.dataset.label ? item.dataset.label + ': ' : '') + '\u20B9' + val;
                    }
                }
            }
        }
    };

    if (type === 'line' || type === 'bar') {
        defaults.scales = {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '\u20B9' + Number(value).toLocaleString('en-IN');
                    }
                }
            }
        };
    }

    return defaults;
}

function mergeDefaults(base, extra) {
    var out = Object.assign({}, base);
    if (!extra) return out;

    Object.keys(extra).forEach(function(key) {
        if (out[key] && typeof out[key] === 'object'
                && typeof extra[key] === 'object'
                && !Array.isArray(out[key]) && !Array.isArray(extra[key])) {
            out[key] = Object.assign({}, out[key], extra[key]);
        } else {
            out[key] = extra[key];
        }
    });
    return out;
}