@extends('layouts.app')
@section('title', 'HRD Management')

@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item active">HRD Management</li>
    </ol>
@endsection


@push('styles')
    <style>
        /* modal grafik diperlebar */
        #detailGrafikModal .modal-dialog {
            max-width: 1100px;
            /* silakan ganti 1100 jadi 1200 kalau mau lebih lebar */
            width: 95%;
        }

        /* Kolom remarks di modal detail grafik */
        #detailGrafikTable td.col-remarks {
            max-width: 300px;
            white-space: normal;
            word-wrap: break-word;
            word-break: break-word;
        }

        /* Tanggal jangan patah baris */
        #detailGrafikTable td.col-tanggal {
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <div class="row w-100">
            <div class="col-md-6 d-flex align-items-center">
                <h3 class="card-title m-0" style="font-weight: Bold;">
                    Hello, {{ Auth::user()->id_admin }} / {{ Auth::user()->nama_staff }}
                </h3>
            </div>
            <div class="col-md-6 text-right">
                <a href="{{ route('hrdmanagement.index') }}" class="btn btn-primary btn-sm">
                    +TAMBAH ABSENSI
                </a>
                <a href="{{ route('hrdmanagement.reportingabsensi') }}" class="btn btn-info btn-sm">
                    REPORT ABSENSI
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">
        {{-- 🔹 NAV TABS --}}
        <ul class="nav nav-tabs mb-3" id="grafikTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-grafik" data-toggle="tab" href="#grafikView" role="tab"
                   aria-controls="grafikView" aria-selected="true">
                    <i class="fas fa-chart-bar mr-1"></i> GRAFIK
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-compare" data-toggle="tab" href="#compareView" role="tab"
                   aria-controls="compareView" aria-selected="false">
                    <i class="fas fa-balance-scale mr-1"></i> COMPARE PERIODE 1 & 2
                </a>
            </li>
        </ul>

        <div class="tab-content" id="grafikTabsContent">

            {{-- ✅ TAB 1: GRAFIK --}}
            <div class="tab-pane fade show active" id="grafikView" role="tabpanel" aria-labelledby="tab-grafik">
                <div class="row">
                    {{-- KOTAK 1 (atas kiri) --}}      
                    <div class="col-md-6 mb-3">
                        <div class="border p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">
                                    DATA SIGN PERIODE {{ $periodeKe }} ALL SITUS ({{ $year }})
                                </h5>

                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                        id="periodeDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Periode {{ $periodeKe }}
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="periodeDropdown">
                                        <a class="dropdown-item {{ $periodeKe == 1 ? 'active' : '' }}"
                                            href="{{ route('hrdmanagement.grafik', ['periode' => 1, 'diagram_periode' => $diagramPeriodeKe]) }}">
                                            Periode 1 (Jan–Jun)
                                        </a>
                                        <a class="dropdown-item {{ $periodeKe == 2 ? 'active' : '' }}"
                                            href="{{ route('hrdmanagement.grafik', ['periode' => 2, 'diagram_periode' => $diagramPeriodeKe]) }}">
                                            Periode 2 (Jul–Des)
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div style="height: 400px;">
                                <canvas id="chartAbsensiTahunan"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- KOTAK 2 (atas kanan) --}}
                    <div class="col-md-6 mb-3">
                        <div class="border p-3 h-100">
                            <h5>HARI INI</h5>
                            <small class="text-muted" style="font-size: 15px;">
                                {{ $todayLabel ?? now()->format('d-m-Y') }}
                            </small>
                            <div style="height: 310px;" class="mt-4">
                                <canvas id="chartDaily"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- KOTAK 3 (bawah kiri) --}}
                    <div class="col-md-6 mb-3">
                        <div class="border p-3 h-100">
                            <h5>DATA SIGN BULAN : {{ strtoupper($bulanSekarangLabel ?? '') }} {{ $year ?? '' }}</h5>
                            <div style="height: 300px;">
                                <canvas id="chartAbsensiBulan"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- KOTAK 4 (bawah kanan) --}}
                    <div class="col-md-6 mb-3">
                        <div class="border p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">
                                    DIAGRAM DATA PERIODE {{ $diagramPeriodeKe }} ALL SITUS ({{ $year }})
                                </h5>

                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                        id="diagramPeriodeDropdown" data-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        Periode {{ $diagramPeriodeKe }}
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="diagramPeriodeDropdown">
                                        <a class="dropdown-item {{ $diagramPeriodeKe == 1 ? 'active' : '' }}"
                                            href="{{ route('hrdmanagement.grafik', ['periode' => $periodeKe, 'diagram_periode' => 1]) }}">
                                            Periode 1 (Jan–Jun)
                                        </a>
                                        <a class="dropdown-item {{ $diagramPeriodeKe == 2 ? 'active' : '' }}"
                                            href="{{ route('hrdmanagement.grafik', ['periode' => $periodeKe, 'diagram_periode' => 2]) }}">
                                            Periode 2 (Jul–Des)
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div style="height: 300px;">
                                <canvas id="chartDiagramSitus"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MODAL DETAIL GRAFIK --}}
                <div class="modal fade" id="detailGrafikModal" tabindex="-1" role="dialog"
                    aria-labelledby="detailGrafikModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-info text-white">
                                <h5 class="modal-title" id="detailGrafikModalLabel">
                                    Detail Absensi: <span id="dg_status"></span> - <span id="dg_bulan"></span>
                                </h5>
                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-bordered table-striped table-hover" id="detailGrafikTable">
                                    <thead>
                                        <tr>
                                            <th style="width:5%;">No</th>
                                            <th>ID Admin</th>
                                            <th>Nama Staff</th>
                                            <th>Situs</th>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                            <th>Periode Cuti</th>             <!-- 6 -->
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- isi via JS --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ✅ TAB 2: COMPARE PERIODE --}}
            <div class="tab-pane fade" id="compareView" role="tabpanel" aria-labelledby="tab-compare">
                @include('pages.hrdmanagement.perbandingangrafik')
            </div>
        </div>
    </div>
</div>
@endsection




@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // === DataTable utk modal detail grafik ===
            let detailTable = $('#detailGrafikTable').DataTable({
                paging: true,
                searching: true,
                lengthChange: true,
                pageLength: 10,
                ordering: true,
                autoWidth: false,
                deferRender: true,
                processing: true,
                language: {
                    emptyTable: "Tidak ada data.",
                    zeroRecords: "Tidak ada data.",
                    processing: "Memproses...",
                    lengthMenu: "Tampilkan _MENU_ data",
                    search: "Cari:",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    infoEmpty: "Menampilkan 0 - 0 dari 0 data",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Berikutnya",
                        previous: "Sebelumnya"
                    }
                },
                columnDefs: [{
                        targets: 0,
                        width: '40px',
                        className: 'text-center'
                    },
                    {
                        targets: 4,
                        className: 'col-tanggal'
                    },
                    {
                        targets: 7,
                        className: 'col-remarks'
                    }
                ]
            });

            function fillDetailTable(rows) {
                detailTable.clear();

                if (!rows || !rows.length) {
                    detailTable.draw();
                    return;
                }

                const dataSet = rows.map(function(row, i) {
                    // hitung teks periode cuti
                    const cutiStart = row.cuti_start;
                    const cutiEnd   = row.cuti_end;

                    let cutiText = '-';
                    if (cutiStart && cutiEnd) {
                        cutiText = `${cutiStart} sd ${cutiEnd}`;
                    }

                    return [
                        i + 1,
                        row.id_admin   ?? '-',
                        row.nama_staff ?? '-',
                        row.nama_situs ?? '-',
                        row.tanggal    ?? '-',
                        row.status     ?? '-',
                        cutiText,
                        row.remarks    ?? '-'
                    ];
                });

                detailTable.rows.add(dataSet).draw();
            }


            // ===================== CHART 1 PERIODE (6 BULAN) =====================
            const ctx = document.getElementById('chartAbsensiTahunan').getContext('2d');

            const labels = @json($labels);
            const bulanNumbers = @json($bulanNumbers);
            const dataTelat = @json($dataTelat);
            const dataSakit = @json($dataSakit);
            const dataIzin = @json($dataIzin);
            const dataTanpaKabar = @json($dataTanpaKabar);
            const dataCuti = @json($dataCuti);

            const statusMap = {
                0: 'TELAT',
                1: 'SAKIT',
                2: 'IZIN',
                3: 'TANPA KABAR',
                4: 'CUTI'
            };

            const chartAbsensiTahunan = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Telat',
                            data: dataTelat,
                            backgroundColor: 'rgb(255,193,7)',
                            borderColor: 'rgb(255,193,7)',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'Sakit',
                            data: dataSakit,
                            backgroundColor: 'rgb(0,123,255) ',
                            borderColor: 'rgb(0,123,255) ',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'Izin',
                            data: dataIzin,
                            backgroundColor: 'rgb(23,162,184)',
                            borderColor: 'rgb(23,162,184)',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'Tanpa Kabar',
                            data: dataTanpaKabar,
                            backgroundColor: 'rgb(220,53,69)',
                            borderColor: 'rgb(220,53,69)',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'Cuti',
                            data: dataCuti,
                            backgroundColor: 'rgb(40,167,69)',
                            borderColor: 'rgb(40,167,69)',
                            borderWidth: 1,
                            borderRadius: 10,
                        },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            enabled: true
                        }
                    },
                    scales: {
                        x: {
                            stacked: false,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            min: 0,
                            max: 30,
                            ticks: {
                                stepSize: 5
                            }
                        }
                    },
                    onClick: function(evt, elements) {
                        if (!elements.length) return;

                        const el = elements[0];
                        const datasetIndex = el.datasetIndex;
                        const dataIndex = el.index;

                        const statusKode = statusMap[datasetIndex];
                        const bulanLabel = labels[dataIndex];
                        const bulanNumber = bulanNumbers[dataIndex];

                        if (!statusKode || !bulanNumber) return;

                        $('#dg_status').text(statusKode);
                        $('#dg_bulan').text(bulanLabel);

                        $.ajax({
                            url: '{{ route('hrdmanagement.grafik.detail') }}',
                            method: 'GET',
                            data: {
                                status: statusKode,
                                bulan: bulanNumber
                            },
                            beforeSend: function() {
                                detailTable.clear().draw(); // kosong dulu (cepat)
                            },
                            success: function(res) {
                                const rows = res.data || res;
                                fillDetailTable(rows);
                                $('#detailGrafikModal').modal('show');
                            },
                            error: function(xhr) {
                                console.log(xhr.responseText);
                                detailTable.clear().draw();
                                $('#detailGrafikModal').modal('show');
                            }
                        });
                    }
                }
            });

            // ===================== CHART DAILY (HARI INI) =====================
            const ctxDaily = document.getElementById('chartDaily').getContext('2d');

            const dailyTelat = @json($dailyTelat);
            const dailySakit = @json($dailySakit);
            const dailyIzin = @json($dailyIzin);
            const dailyTanpaKabar = @json($dailyTanpaKabar);
            const dailyCuti = @json($dailyCuti);
            const todayLabel = @json($todayLabel);

            const statusFromDatasetDaily = [
                'TELAT',
                'SAKIT',
                'IZIN',
                'TANPA KABAR',
                'CUTI'
            ];

            const chartDaily = new Chart(ctxDaily, {
                type: 'bar',
                data: {
                    labels: ['Telat', 'Sakit', 'Izin', 'Tanpa Kabar', 'Cuti'],
                    datasets: [{
                            label: 'Telat',
                            data: [dailyTelat, 0, 0, 0, 0],
                            backgroundColor: 'rgb(255,193,7)',
                            borderColor: 'rgb(255,193,7)',
                            borderWidth: 1,
                            borderRadius: 10,
                        },
                        {
                            label: 'Sakit',
                            data: [0, dailySakit, 0, 0, 0],
                            backgroundColor: 'rgb(0,123,255)',
                            borderColor: 'rgb(0,123,255)',
                            borderWidth: 1,
                            borderRadius: 10,
                        },
                        {
                            label: 'Izin',
                            data: [0, 0, dailyIzin, 0, 0],
                            backgroundColor: 'rgb(23,162,184)',
                            borderColor: 'rgb(23,162,184)',
                            borderWidth: 1,
                            borderRadius: 10,
                        },
                        {
                            label: 'Tanpa Kabar',
                            data: [0, 0, 0, dailyTanpaKabar, 0],
                            backgroundColor: 'rgb(220,53,69)',
                            borderColor: 'rgb(220,53,69)',
                            borderWidth: 1,
                            borderRadius: 10,
                        },
                        {
                            label: 'Cuti',
                            data: [0, 0, 0, 0, dailyCuti],
                            backgroundColor: 'rgb(40,167,69)',
                            borderColor: 'rgb(40,167,69)',
                            borderWidth: 1,
                            borderRadius: 10,
                        },
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            enabled: true
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            beginAtZero: true,
                            // paksa maksimum skala, misal 10 orang
                            max: 5,                 // 🔹 atau 5 / 20 terserah kebutuhan
                            ticks: {
                                stepSize: 1       // biar tiap 1 orang
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        y: {
                            stacked: true,
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 14
                                }
                            }
                        }
                    },
                    onClick: function(evt, elements) {
                        if (!elements.length) return;

                        const el = elements[0];
                        const dsIndex = el.datasetIndex; // 🔹 sekarang pakai datasetIndex
                        const statusKode = statusFromDatasetDaily[dsIndex];
                        if (!statusKode) return;

                        $('#dg_status').text(statusKode);
                        $('#dg_bulan').text(todayLabel);

                        $.ajax({
                            url: '{{ route('hrdmanagement.grafik.daily_detail') }}',
                            method: 'GET',
                            data: {
                                status: statusKode
                            },
                            beforeSend: function() {
                                detailTable.clear().draw();
                            },
                            success: function(res) {
                                const rows = res.data || res;
                                fillDetailTable(rows);
                                $('#detailGrafikModal').modal('show');
                            },
                            error: function(xhr) {
                                console.log(xhr.responseText);
                                detailTable.clear().draw();
                                $('#detailGrafikModal').modal('show');
                            }
                        });
                    }
                }
            });

            // ===================== CHART 1 BULAN (5 BATANG) =====================
            const ctxBulan = document.getElementById('chartAbsensiBulan').getContext('2d');

            const telatBulan = @json($bulanTelat);
            const sakitBulan = @json($bulanSakit);
            const izinBulan = @json($bulanIzin);
            const tanpaKabarBulan = @json($bulanTanpaKabar);
            const cutiBulan = @json($bulanCuti);
            const currentMonthNumber = @json($currentMonthNumber);

            const statusFromDatasetBulan = [
                'TELAT',
                'SAKIT',
                'IZIN',
                'TANPA KABAR',
                'CUTI'
            ];

            const chartAbsensiBulan = new Chart(ctxBulan, {
                type: 'bar',
                data: {
                    labels: ['Telat', 'Sakit', 'Izin', 'Tanpa Kabar', 'Cuti'],
                    datasets: [{
                            label: 'Telat',
                            data: [telatBulan, 0, 0, 0, 0],
                            backgroundColor: 'rgb(255,193,7)',
                            borderColor: 'rgb(255,193,7)',
                            borderRadius: 10,
                        },
                        {
                            label: 'Sakit',
                            data: [0, sakitBulan, 0, 0, 0],
                            backgroundColor: 'rgb(0,123,255)',
                            borderColor: 'rgb(0,123,255)',
                            borderRadius: 10,
                        },
                        {
                            label: 'Izin',
                            data: [0, 0, izinBulan, 0, 0],
                            backgroundColor: 'rgb(23,162,184)',
                            borderColor: 'rgb(23,162,184)',
                            borderRadius: 10,
                        },
                        {
                            label: 'Tanpa Kabar',
                            data: [0, 0, 0, tanpaKabarBulan, 0],
                            backgroundColor: 'rgb(220,53,69)',
                            borderColor: 'rgb(220,53,69)',
                            borderRadius: 10,
                        },
                        {
                            label: 'Cuti',
                            data: [0, 0, 0, 0, cutiBulan],
                            backgroundColor: 'rgb(40,167,69)',
                            borderColor: 'rgb(40,167,69)',
                            borderRadius: 10,
                        },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            enabled: true
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            max: 20,
                            ticks: {
                                stepSize: 5
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        }
                    },
                    onClick: function(evt, elements) {
                        if (!elements.length) return;

                        const el = elements[0];
                        const dsIndex = el.datasetIndex;
                        const statusKode = statusFromDatasetBulan[dsIndex];
                        if (!statusKode) return;

                        $('#dg_status').text(statusKode);
                        $('#dg_bulan').text('{{ $bulanSekarangLabel }}');

                        $.ajax({
                            url: '{{ route('hrdmanagement.grafik.detail') }}',
                            method: 'GET',
                            data: {
                                status: statusKode,
                                bulan: currentMonthNumber
                            },
                            beforeSend: function() {
                                detailTable.clear().draw();
                            },
                            success: function(res) {
                                const rows = res.data || res;
                                fillDetailTable(rows);
                                $('#detailGrafikModal').modal('show');
                            },
                            error: function(xhr) {
                                console.log(xhr.responseText);
                                detailTable.clear().draw();
                                $('#detailGrafikModal').modal('show');
                            }
                        });
                    }
                }
            });

            // ===================== DIAGRAM SITUS (PIE) =====================
            const ctxDiagram = document.getElementById('chartDiagramSitus').getContext('2d');

            const situsLabels = @json($diagramLabels);
            const situsTotals = @json($diagramTotals);
            const situsDetail = @json($diagramDetail);

            const bgColors = [];
            const borderColors = [];
            const totalSites = situsLabels.length || 1;

            for (let i = 0; i < totalSites; i++) {
                const hue = (i * 360 / totalSites);
                bgColors.push(`hsl(${hue}, 80%, 50%)`);
                borderColors.push(`hsl(${hue}, 70%, 40%)`);
            }

            new Chart(ctxDiagram, {
                type: 'pie',
                data: {
                    labels: situsLabels,
                    datasets: [{
                        data: situsTotals,
                        backgroundColor: bgColors,
                        borderColor: borderColors,
                        borderWidth: 3,
                        hoverOffset: 8,
                    }]
                },
                options: {
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
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            enabled: true,
                            bodyFont: {
                                family: 'Courier New, monospace',
                                size: 11
                            },
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const detail = situsDetail[label] || {
                                        telat: 0,
                                        sakit: 0,
                                        izin: 0,
                                        tanpa_kabar: 0,
                                        cuti: 0
                                    };

                                    const total =
                                        detail.telat +
                                        detail.sakit +
                                        detail.izin +
                                        detail.tanpa_kabar +
                                        detail.cuti;

                                    const pad = (txt) => txt.padEnd(13);

                                    return [
                                        pad('Situs') + ': ' + label,
                                        pad('Telat') + ': ' + detail.telat,
                                        pad('Sakit') + ': ' + detail.sakit,
                                        pad('Izin') + ': ' + detail.izin,
                                        pad('Tanpa Kabar') + ': ' + detail.tanpa_kabar,
                                        pad('Cuti') + ': ' + detail.cuti,
                                        '-----------------',
                                        pad('Total') + ': ' + total
                                    ];
                                }
                            }
                        }
                    },
                    onClick: function(evt, elements) {
                        if (!elements.length) return;

                        const el = elements[0];
                        const label = this.data.labels[el.index];

                        $('#dg_status').text('SITUS');
                        $('#dg_bulan').text(label + ' (6 bulan)');

                        $.ajax({
                            url: '{{ route('hrdmanagement.grafik.diagram_detail') }}',
                            method: 'GET',
                            data: {
                                situs: label
                            },
                            beforeSend: function() {
                                detailTable.clear().draw();
                            },
                            success: function(res) {
                                const rows = res.data || res;
                                fillDetailTable(rows);
                                $('#detailGrafikModal').modal('show');
                            },
                            error: function(xhr) {
                                console.log(xhr.responseText);
                                detailTable.clear().draw();
                                $('#detailGrafikModal').modal('show');
                            }
                        });
                    }
                }
            });
        });
    </script>
@endpush
