@props(['chart', 'id' => 'growthChart', 'height' => 320, 'caption' => null])
<div class="ng-chart"
     id="{{ $id }}"
     role="img"
     data-chart="{{ json_encode($chart) }}"
     data-height="{{ (int) $height }}"
     aria-label="Grafik berat badan menurut usia untuk {{ $chart['child_name'] }}. Rentang tumbuh yang diharapkan ditandai garis hijau, sedangkan titik gelap adalah hasil pengukuran anak."
     style="min-height: {{ $height }}px;"></div>
<div class="ng-chart-legend">
    <span><i style="background:#1c6b45;"></i>Garis tengah anak seusia</span>
    <span><i style="background:#8fd0ab;"></i>Rentang tumbuh yang diharapkan</span>
    <span><i style="background:#e2b93b;"></i>Batas bawah rentang</span>
    <span><i style="background:#132a1f;"></i>Titik pengukuran anak</span>
</div>
@if ($caption)
    <p class="ng-tiny ng-muted mt-1 mb-0">{{ $caption }}</p>
@endif

@pushOnce('scripts')
<script src="{{ asset('boron/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script>
    (function () {
        const render = (el) => {
            if (!el || typeof ApexCharts === 'undefined' || el.dataset.ngRendered) return;
            el.dataset.ngRendered = '1';

            const chart = JSON.parse(el.dataset.chart);
            const options = {
                chart: {
                    type: 'line',
                    height: Number(el.dataset.height || 320),
                    fontFamily: 'Lexend, sans-serif',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    animations: { enabled: true, speed: 350 },
                },
                series: [
                    { name: 'Batas bawah rentang', data: chart.labels.map((x, i) => ({ x: x, y: chart.lowerThree[i] })), color: '#e2b93b', type: 'line' },
                    { name: 'Rentang bawah', data: chart.labels.map((x, i) => ({ x: x, y: chart.lower[i] })), color: '#8fd0ab', type: 'line' },
                    { name: 'Garis tengah anak seusia', data: chart.labels.map((x, i) => ({ x: x, y: chart.median[i] })), color: '#1c6b45', type: 'line' },
                    { name: 'Rentang atas', data: chart.labels.map((x, i) => ({ x: x, y: chart.upper[i] })), color: '#8fd0ab', type: 'line' },
                    { name: 'Pengukuran anak', data: chart.points.map((p) => ({ x: p.x, y: p.y })), color: '#132a1f', type: 'scatter' },
                ],
                stroke: {
                    width: [1.5, 2, 3, 2, 0],
                    curve: 'smooth',
                    dashArray: [5, 0, 0, 0, 0],
                },
                markers: { size: [0, 0, 0, 0, 5], strokeWidth: 2, strokeColors: '#fff', hover: { size: 7 } },
                colors: ['#e2b93b', '#8fd0ab', '#1c6b45', '#8fd0ab', '#132a1f'],
                dataLabels: { enabled: false },
                legend: { show: false },
                grid: { borderColor: '#d9e6dd', strokeDashArray: 4, padding: { left: 8, right: 12 } },
                xaxis: {
                    type: 'numeric',
                    min: 0,
                    max: chart.labels[chart.labels.length - 1],
                    tickAmount: 10,
                    title: { text: chart.x_label, style: { fontSize: '11px', fontWeight: 700, color: '#52675c' } },
                    labels: { style: { fontSize: '10px', colors: '#52675c' }, formatter: (v) => Math.round(v) },
                },
                yaxis: {
                    title: { text: chart.y_label, style: { fontSize: '11px', fontWeight: 700, color: '#52675c' } },
                    labels: { style: { fontSize: '10px', colors: '#52675c' }, formatter: (v) => Number(v).toFixed(0) },
                },
                tooltip: {
                    shared: true,
                    y: { formatter: (v) => (v === null ? '-' : Number(v).toFixed(1) + ' kg') },
                    x: { formatter: (v) => 'Usia ' + Number(v).toFixed(1) + ' bulan' },
                },
                noData: { text: 'Belum ada data pengukuran' },
            };

            new ApexCharts(el, options).render();
        };

        document.querySelectorAll('.ng-chart[data-chart]').forEach(render);
    })();
</script>
@endPushOnce
