<div class="p-3">
    <h5 class="mb-3 font-weight-bold text-primary">Perbandingan Periode 1 &amp; 2</h5>
    <p class="text-muted mb-4">
        Grafik ini menampilkan perbandingan data absensi antar dua periode (Jan–Jun dan Jul–Des) di semua situs.
    </p>

    {{-- ================== PERIODE 1 (JAN–JUN) ================== --}}
    <div class="mb-4">
        <div class="border p-3">
            <h6 class="text-center mb-3">Periode 1 (Jan–Jun)</h6>

            <div class="row">
                {{-- Bar chart --}}
                <div class="col-md-8 mb-3 mb-md-0">
                    <div style="height: 320px;">
                        <canvas id="chartCompareP1Bar"></canvas>
                    </div>
                </div>

                {{-- Pie chart --}}
                <div class="col-md-4">
                    <div style="height: 320px;">
                        <canvas id="chartCompareP1Pie"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================== PERIODE 2 (JUL–DES) ================== --}}
    <div class="mb-2">
        <div class="border p-3">
            <h6 class="text-center mb-3">Periode 2 (Jul–Des)</h6>

            <div class="row">
                {{-- Bar chart --}}
                <div class="col-md-8 mb-3 mb-md-0">
                    <div style="height: 320px;">
                        <canvas id="chartCompareP2Bar"></canvas>
                    </div>
                </div>

                {{-- Pie chart --}}
                <div class="col-md-4">
                    <div style="height: 320px;">
                        <canvas id="chartCompareP2Pie"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ================== DATA DARI CONTROLLER ==================

    // Periode 1 (Jan–Jun) - bar
    const labelsP1         = @json($cmpLabels1 ?? []);
    const dataTelatP1      = @json($cmpTelat1 ?? []);
    const dataSakitP1      = @json($cmpSakit1 ?? []);
    const dataIzinP1       = @json($cmpIzin1 ?? []);
    const dataTanpaKabarP1 = @json($cmpTanpa1 ?? []);
    const dataCutiP1       = @json($cmpCuti1 ?? []);

    // Periode 1 - pie
    const situsLabelsP1 = @json($cmpDiagramLabels1 ?? []);
    const situsTotalsP1 = @json($cmpDiagramTotals1 ?? []);
    const situsDetailP1 = @json($cmpDiagramDetail1 ?? []);

    // Periode 2 (Jul–Des) - bar
    const labelsP2         = @json($cmpLabels2 ?? []);
    const dataTelatP2      = @json($cmpTelat2 ?? []);
    const dataSakitP2      = @json($cmpSakit2 ?? []);
    const dataIzinP2       = @json($cmpIzin2 ?? []);
    const dataTanpaKabarP2 = @json($cmpTanpa2 ?? []);
    const dataCutiP2       = @json($cmpCuti2 ?? []);

    // Periode 2 - pie
    const situsLabelsP2 = @json($cmpDiagramLabels2 ?? []);
    const situsTotalsP2 = @json($cmpDiagramTotals2 ?? []);
    const situsDetailP2 = @json($cmpDiagramDetail2 ?? []);

    // ================== HELPER: DATASET BAR ==================
    function buildBarDatasets(telat, sakit, izin, tanpaKabar, cuti) {
        return [
            {
                label: 'Telat',
                data: telat,
                backgroundColor: 'rgb(255,193,7)',
                borderColor: 'rgb(255,193,7)',
                borderWidth: 1,
                borderRadius: 4,
            },
            {
                label: 'Sakit',
                data: sakit,
                backgroundColor: 'rgb(0,123,255)',
                borderColor: 'rgb(0,123,255)',
                borderWidth: 1,
                borderRadius: 4,
            },
            {
                label: 'Izin',
                data: izin,
                backgroundColor: 'rgb(23,162,184)',
                borderColor: 'rgb(23,162,184)',
                borderWidth: 1,
                borderRadius: 4,
            },
            {
                label: 'Tanpa Kabar',
                data: tanpaKabar,
                backgroundColor: 'rgb(220,53,69)',
                borderColor: 'rgb(220,53,69)',
                borderWidth: 1,
                borderRadius: 4,
            },
            {
                label: 'Cuti',
                data: cuti,
                backgroundColor: 'rgb(40,167,69)',
                borderColor: 'rgb(40,167,69)',
                borderWidth: 1,
                borderRadius: 10,
                hidden: true,
            },
        ];
    }

    const barOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
            },
            tooltip: {
                enabled: true,
            },
        },
        scales: {
            x: {
                stacked: false,
                grid: { display: false },
            },
            y: {
                beginAtZero: true,
                min: 0,
                max: 30,     // silakan ubah sesuai kebutuhan
                ticks: {
                    stepSize: 5,
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)',
                },
            },
        },
    };

    function createBarChart(canvasId, labels, telat, sakit, izin, tanpaKabar, cuti) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: buildBarDatasets(telat, sakit, izin, tanpaKabar, cuti),
            },
            options: barOptions,
        });
    }

    // ================== BAR CHART ==================
    createBarChart(
        'chartCompareP1Bar',
        labelsP1,
        dataTelatP1,
        dataSakitP1,
        dataIzinP1,
        dataTanpaKabarP1,
        dataCutiP1
    );

    createBarChart(
        'chartCompareP2Bar',
        labelsP2,
        dataTelatP2,
        dataSakitP2,
        dataIzinP2,
        dataTanpaKabarP2,
        dataCutiP2
    );

    // ================== HELPER: PIE ==================
    function buildPieColors(labels) {
        const bg = [];
        const border = [];
        const total = labels.length || 1;

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
                        boxWidth: 14,
                        boxHeight: 14,
                        padding: 15,
                        font: { size: 11 },
                    },
                },
                tooltip: {
                    enabled: true,
                    bodyFont: {
                        family: 'Courier New, monospace',
                        size: 11,
                    },
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const d = detailMap[label] || {
                                telat: 0,
                                sakit: 0,
                                izin: 0,
                                tanpa_kabar: 0,
                                cuti: 0,
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

    function createPieChart(canvasId, labels, totals, detailMap) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const { bg, border } = buildPieColors(labels);

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: totals,
                    backgroundColor: bg,
                    borderColor: border,
                    borderWidth: 3,
                    hoverOffset: 8,
                }],
            },
            options: buildPieOptions(detailMap),
        });
    }

    // ================== PIE CHART ==================
    createPieChart('chartCompareP1Pie', situsLabelsP1, situsTotalsP1, situsDetailP1);
    createPieChart('chartCompareP2Pie', situsLabelsP2, situsTotalsP2, situsDetailP2);
});
</script>
@endpush
