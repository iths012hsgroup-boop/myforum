@extends('layouts.app')
@section('title', 'Report Absensi')

@push('styles')
    <style>
        .table-hover > tbody > tr:hover {
            background-color: #fffac2 !important;
        }

        /* DataTables lebih rapi */
        .dataTables_wrapper .row {
            margin-bottom: 15px;
        }
    </style>
@endpush

@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('hrdmanagement.grafik') }}">Kembali</a></li>
        <li class="breadcrumb-item active">Report Absensi</li>
    </ol>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Report Absensi</h3>
        </div>
        <div class="card-body">

            {{-- Form Filter + Generate + Export --}}
            <form id="form-generate" class="form-inline justify-content-end mb-3" onsubmit="return false;">
                <input
                    type="number"
                    class="form-control form-control-sm mr-2"
                    id="gen_tahun"
                    placeholder="Tahun"
                    value="{{ date('Y') }}"
                >

<select id="gen_periode_ke" class="form-control form-control-sm mr-2">
    <option value="">Semua Periode</option>
    @foreach ($periodes as $p)
        <option value="{{ $p->periode }}">
            Periode {{ $p->periode }} ({{ $p->bulan_dari }}–{{ $p->bulan_ke }})
        </option>
    @endforeach
</select>

                {{-- PILIH SITUS --}}
                <select id="gen_situs" class="form-control form-control-sm mr-2">
                    <option value="">Semua Situs</option>
                    @foreach ($sites as $situs)
                        <option value="{{ $situs->id }}">{{ $situs->nama_situs }}</option>
                    @endforeach
                </select>

                <button type="button" id="btn-generate" class="btn btn-sm btn-primary">
                    Generate Report
                </button>
                <button type="button" id="btn-export" class="btn btn-sm btn-success ml-2">
                    Export Excel
                </button>
            </form>

            <table id="absensiTable" class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID Admin</th>
                        <th>Nama Staff</th>
                        <th>Nama Situs</th>
                        <th>Periode</th>
                        <th>Sakit</th>
                        <th>Izin</th>
                        <th>Telat</th>
                        <th>Tanpa Kabar</th>
                        <th>Cuti</th>
                        <th>Total Absensi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    // ================== ROUTES ==================
    const routes = {
        data    : '{{ route('hrdmanagement.reportingabsensi.data') }}',
        generate: '{{ route('hrdmanagement.reportingabsensi.generate') }}',
        export  : '{{ route('hrdmanagement.reportingabsensi.export') }}',
    };

    const $tahun      = $('#gen_tahun');
    const $periodeKe  = $('#gen_periode_ke');
    const $situs      = $('#gen_situs');
    const $btnGenerate= $('#btn-generate');
    const $btnExport  = $('#btn-export');

    // ================== DATATABLE ==================
    const table = $('#absensiTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: routes.data,
            data: function (d) {
                const tahun     = $tahun.val();
                const periodeKe = $periodeKe.val();
                const situsVals = $situs.val(); // single select => string atau null

                d.tahun = tahun || '';

                if (situsVals) {
                    d.id_situs = [situsVals];   // kirim sebagai array: ["3"]
                } else {
                    d.id_situs = [];
                }

                if (tahun && periodeKe) {
                    d.periode = `${tahun}-${periodeKe}`;
                }
            }
        },
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                render: (data, type, row, meta) => meta.row + 1,
            },
            { data: 'id_admin',      name: 'id_admin' },
            { data: 'nama_staff',    name: 'nama_staff' },
            { data: 'nama_situs',    name: 'nama_situs' },
            { data: 'periode',       name: 'periode' },
            { data: 'sakit',         name: 'sakit' },
            { data: 'izin',          name: 'izin' },
            { data: 'telat',         name: 'telat' },
            { data: 'tanpa_kabar',   name: 'tanpa_kabar' },
            { data: 'cuti',          name: 'cuti' },
            { data: 'total_absensi', name: 'total_absensi' },
        ],
        responsive: true,
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        language: {
            lengthMenu  : 'Tampilkan _MENU_ data per halaman',
            zeroRecords : 'Tidak ada data yang ditemukan',
            info        : 'Menampilkan halaman _PAGE_ dari _PAGES_',
            infoEmpty   : 'Tidak ada data',
            infoFiltered: '(difilter dari total _MAX_ data)',
            search      : 'Cari:',
            paginate    : {
                first   : 'Awal',
                last    : 'Akhir',
                next    : 'Berikutnya',
                previous: 'Sebelumnya',
            },
        },
    });

    // reload otomatis kalau filter berubah
    $tahun.add($periodeKe).add($situs).on('change', function () {
        table.ajax.reload();
    });

    // ================== GENERATE REPORT (SweetAlert) ==================
    function callGenerate(periodeStr, callback) {
        $.ajax({
            url   : routes.generate,
            method: 'POST',
            data  : {
                _token : '{{ csrf_token() }}',
                periode: periodeStr,
            },
            success(res) {
                console.log('GENERATE', periodeStr, res);
                if (typeof callback === 'function') callback(res);
            },
            error(xhr) {
                console.error('SERVER ERROR:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Terjadi kesalahan di server untuk periode ' + periodeStr,
                });
            }
        });
    }

    $btnGenerate.on('click', function () {
        const tahun     = $tahun.val();
        const periodeKe = $periodeKe.val(); // '' / '1' / '2'

        if (!tahun) {
            Swal.fire({
                icon: 'warning',
                title: 'Tahun wajib diisi',
                text: 'Silakan isi tahun terlebih dahulu sebelum generate report.',
            });
            return;
        }

        // semua periode → generate 1 & 2
        if (!periodeKe) {
            const periode1 = `${tahun}-1`;
            const periode2 = `${tahun}-2`;

            callGenerate(periode1, function (res1) {
                callGenerate(periode2, function (res2) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Generate selesai',
                        html: `
                            Periode 1: ${(res1 && res1.message) || 'OK'}<br>
                            Periode 2: ${(res2 && res2.message) || 'OK'}
                        `,
                    });
                    table.ajax.reload();
                });
            });
            return;
        }

        // hanya satu periode
        const periode = `${tahun}-${periodeKe}`;

        callGenerate(periode, function (res) {
            if (res && res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: (res.message || 'Generate selesai.') + ' Total baris: ' + res.total,
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: (res && res.message) || 'Gagal generate report.',
                });
            }
            table.ajax.reload();
        });
    });

    // ================== EXPORT EXCEL (SweetAlert confirm) ==================
    $btnExport.on('click', function () {
        const tahun     = $tahun.val();
        const periodeKe = $periodeKe.val(); // bisa kosong
        const situsVal  = $situs.val();     // single value

        if (!tahun) {
            Swal.fire({
                icon: 'warning',
                title: 'Tahun wajib diisi',
                text: 'Silakan isi tahun terlebih dahulu sebelum export.',
            });
            return;
        }

        const url = new URL(routes.export, window.location.origin);
        url.searchParams.set('tahun', tahun);

        if (periodeKe) {
            url.searchParams.set('periode_ke', periodeKe);
        }
        if (situsVal) {
            url.searchParams.set('id_situs', situsVal);
        }

const keteranganPeriode = periodeKe
    ? (`Periode ${periodeKe} (${(window.PERIODE_LABELS || {})[periodeKe] || '-'})`)
    : 'Semua Periode';

        const keteranganSitus = situsVal
            ? $('#gen_situs option:selected').text()
            : 'Semua Situs';

        Swal.fire({
            icon: 'question',
            title: 'Export Excel?',
            html: `
                Tahun: <b>${tahun}</b><br>
                Periode: <b>${keteranganPeriode}</b><br>
                Situs: <b>${keteranganSitus}</b>
            `,
            showCancelButton: true,
            confirmButtonText: 'Ya, export sekarang',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url.toString();
            }
        });
    });
});


    // Map periode -> "bulan_dari–bulan_ke"
    window.PERIODE_LABELS = @json(
        $periodes->mapWithKeys(function ($p) {
            return [
                (string) $p->periode => $p->bulan_dari . '–' . $p->bulan_ke,
            ];
        })
    );
</script>
@endpush

