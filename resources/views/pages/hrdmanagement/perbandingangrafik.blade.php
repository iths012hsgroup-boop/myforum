<div class="p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1 font-weight-bold text-primary">Compare Periode 1 &amp; 2</h5>
            <p class="text-muted mb-0">
                Grafik ini menampilkan perbandingan data absensi antar dua periode (Jan–Jun dan Jul–Des) di semua situs.
            </p>
        </div>

        <form method="GET" id="compareYearForm" class="ml-3">
            <div class="form-inline">
                <label for="compare_year" class="mr-2 mb-0">Tahun:</label>
                <select name="compare_year" id="compare_year" class="form-control form-control-sm">
                    @foreach($compareYears ?? [] as $y)
                        <option value="{{ $y }}" {{ ($y == ($compareYear ?? null)) ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @php
        $periods = [
            [
                'key'   => 1,
                'title' => 'Periode 1 (Jan–Jun)',
                'barId' => 'chartCompareP1Bar',
                'pieId' => 'chartCompareP1Pie',
            ],
            [
                'key'   => 2,
                'title' => 'Periode 2 (Jul–Des)',
                'barId' => 'chartCompareP2Bar',
                'pieId' => 'chartCompareP2Pie',
            ],
        ];
    @endphp

    @foreach($periods as $p)
        <div class="mb-4">
            <div class="border p-3">
                <h6 class="text-center mb-3">{{ $p['title'] }}</h6>

                <div class="row">
                    <div class="col-md-8 mb-3 mb-md-0">
                        <div style="height: 320px;">
                            <canvas id="{{ $p['barId'] }}"></canvas>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div style="height: 320px;">
                            <canvas id="{{ $p['pieId'] }}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ================== DATA AWAL DARI CONTROLLER ==================
    const initial = {
        p1: {
            labels        : @json($cmpLabels1         ?? []),
            telat         : @json($cmpTelat1          ?? []),
            sakit         : @json($cmpSakit1          ?? []),
            izin          : @json($cmpIzin1           ?? []),
            tanpaKabar    : @json($cmpTanpa1          ?? []),
            cuti          : @json($cmpCuti1           ?? []),
            situsLabels   : @json($cmpDiagramLabels1  ?? []),
            situsTotals   : @json($cmpDiagramTotals1  ?? []),
            situsDetail   : @json($cmpDiagramDetail1  ?? []),
        },
        p2: {
            labels        : @json($cmpLabels2         ?? []),
            telat         : @json($cmpTelat2          ?? []),
            sakit         : @json($cmpSakit2          ?? []),
            izin          : @json($cmpIzin2           ?? []),
            tanpaKabar    : @json($cmpTanpa2          ?? []),
            cuti          : @json($cmpCuti2           ?? []),
            situsLabels   : @json($cmpDiagramLabels2  ?? []),
            situsTotals   : @json($cmpDiagramTotals2  ?? []),
            situsDetail   : @json($cmpDiagramDetail2  ?? []),
        }
    };

    // ================== HELPER: BAR DATASET & OPTIONS ==================
    const barColors = [
        'rgb(255,193,7)',   // Telat
        'rgb(0,123,255)',   // Sakit
        'rgb(23,162,184)',  // Izin
        'rgb(220,53,69)',   // Tanpa Kabar
        'rgb(40,167,69)',   // Cuti
    ];
    const barLabels = ['Telat','Sakit','Izin','Tanpa Kabar','Cuti'];

    function buildBarDatasets(telat, sakit, izin, tanpaKabar, cuti) {
        const sources = [telat, sakit, izin, tanpaKabar, cuti];

        return sources.map((dataArr, i) => ({
            label        : barLabels[i],
            data         : dataArr,
            backgroundColor: barColors[i],
            borderColor  : barColors[i],
            borderWidth  : 1,
            borderRadius : i === 4 ? 10 : 4,
        }));
    }

    const barOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'bottom' },
            tooltip: { enabled: true },
        },
        scales: {
            x: { stacked: false, grid: { display: false } },
            y: {
                beginAtZero: true,
                min: 0,
                max: 30,
                ticks: { stepSize: 5 },
                grid: { color: 'rgba(0,0,0,0.05)' },
            },
        },
    };

    function createBarChart(canvasId, cfg) {
        const el = document.getElementById(canvasId);
        if (!el) return null;
        const ctx = el.getContext('2d');

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: cfg.labels,
                datasets: buildBarDatasets(
                    cfg.telat,
                    cfg.sakit,
                    cfg.izin,
                    cfg.tanpaKabar,
                    cfg.cuti
                ),
            },
            options: barOptions,
        });
    }

    function updateBarChart(chart, cfg) {
        if (!chart) return;
        chart.data.labels = cfg.labels;

        const sources = [cfg.telat, cfg.sakit, cfg.izin, cfg.tanpaKabar, cfg.cuti];
        chart.data.datasets.forEach((ds, i) => {
            ds.data = sources[i] || [];
        });

        chart.update();
    }

    // ================== HELPER: PIE ==================
    function buildPieColors(count) {
        const bg = [], border = [];
        const total = count || 1;

        for (let i = 0; i < total; i++) {
            const hue = (i * 360 / total);
            bg.push(`hsl(${hue}, 80%, 50%)`);
            border.push(`hsl(${hue}, 70%, 40%)`);
        }
        return { bg, border };
    }

    function buildPieOptions(detailMap) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        boxWidth : 14,
                        boxHeight: 14,
                        padding  : 15,
                        font     : { size: 11 },
                    },
                },
                tooltip: {
                    enabled: true,
                    bodyFont: { family: 'Courier New, monospace', size: 11 },
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const d = detailMap[label] || {
                                telat: 0, sakit: 0, izin: 0, tanpa_kabar: 0, cuti: 0
                            };
                            const total = d.telat + d.sakit + d.izin + d.tanpa_kabar + d.cuti;
                            const pad = (txt) => txt.padEnd(13);

                            return [
                                pad('Situs') + ': ' + label,
                                pad('Telat') + ': ' + d.telat,
                                pad('Sakit') + ': ' + d.sakit,
                                pad('Izin') + ': ' + d.izin,
                                pad('Tanpa Kabar') + ': ' + d.tanpa_kabar,
                                pad('Cuti') + ': ' + d.cuti,
                                '-----------------',
                                pad('Total') + ': ' + total,
                            ];
                        },
                    },
                },
            },
        };
    }

    function createPieChart(canvasId, cfg) {
        const el = document.getElementById(canvasId);
        if (!el) return null;

        const ctx = el.getContext('2d');
        const { bg, border } = buildPieColors(cfg.situsLabels.length);

        return new Chart(ctx, {
            type: 'pie',
            data: {
                labels: cfg.situsLabels,
                datasets: [{
                    data           : cfg.situsTotals,
                    backgroundColor: bg,
                    borderColor    : border,
                    borderWidth    : 3,
                    hoverOffset    : 8,
                }],
            },
            options: buildPieOptions(cfg.situsDetail),
        });
    }

    function updatePieChart(chart, cfg) {
        if (!chart) return;

        const { bg, border } = buildPieColors(cfg.situsLabels.length);

        chart.data.labels                    = cfg.situsLabels;
        chart.data.datasets[0].data          = cfg.situsTotals;
        chart.data.datasets[0].backgroundColor = bg;
        chart.data.datasets[0].borderColor     = border;
        chart.options                        = buildPieOptions(cfg.situsDetail);

        chart.update();
    }

    // ================== INIT CHART PERTAMA ==================
    const charts = {
        p1: {
            bar: createBarChart('chartCompareP1Bar', initial.p1),
            pie: createPieChart('chartCompareP1Pie', initial.p1),
        },
        p2: {
            bar: createBarChart('chartCompareP2Bar', initial.p2),
            pie: createPieChart('chartCompareP2Pie', initial.p2),
        },
    };

    // ================== DROPDOWN TAHUN: UPDATE VIA AJAX ==================
    const yearSelect = document.getElementById('compare_year');

    if (yearSelect) {
        yearSelect.addEventListener('change', function () {
            const year = this.value || '';

            fetch(`{{ route('hrdmanagement.grafik.compare_data') }}?year=${encodeURIComponent(year)}`)
                .then(res => res.json())
                .then(data => {
                    const cfg = {
                        p1: {
                            labels      : data.cmpLabels1        || [],
                            telat       : data.cmpTelat1         || [],
                            sakit       : data.cmpSakit1         || [],
                            izin        : data.cmpIzin1          || [],
                            tanpaKabar  : data.cmpTanpa1         || [],
                            cuti        : data.cmpCuti1          || [],
                            situsLabels : data.cmpDiagramLabels1 || [],
                            situsTotals : data.cmpDiagramTotals1 || [],
                            situsDetail : data.cmpDiagramDetail1 || [],
                        },
                        p2: {
                            labels      : data.cmpLabels2        || [],
                            telat       : data.cmpTelat2         || [],
                            sakit       : data.cmpSakit2         || [],
                            izin        : data.cmpIzin2          || [],
                            tanpaKabar  : data.cmpTanpa2         || [],
                            cuti        : data.cmpCuti2          || [],
                            situsLabels : data.cmpDiagramLabels2 || [],
                            situsTotals : data.cmpDiagramTotals2 || [],
                            situsDetail : data.cmpDiagramDetail2 || [],
                        },
                    };

                    updateBarChart(charts.p1.bar, cfg.p1);
                    updateBarChart(charts.p2.bar, cfg.p2);
                    updatePieChart(charts.p1.pie, cfg.p1);
                    updatePieChart(charts.p2.pie, cfg.p2);

                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', 'compare');
                    url.searchParams.set('compare_year', year);
                    window.history.replaceState({}, '', url);
                })
                .catch(() => {
                    alert('Gagal memuat data tahun ' + year);
                });
        });
    }
});
</script>
@endpush
