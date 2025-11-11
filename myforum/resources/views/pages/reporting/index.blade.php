@extends('layouts.app')
@section('title', 'Reporting')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item active">Reporting</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Reporting</h3>
            <button class="btn btn-primary float-right" data-toggle="modal" data-target="#generateReportModal">Generate
                Report</button>
            <button class="btn btn-secondary float-right mr-2" data-toggle="modal" data-target="#exportReportModal">Export
                Report</button>
        </div>
        <div class="card-body">
            <div class="float-right">
                <form id="searchForm" action="{{ route('reporting.index') }}" method="GET" class="form-inline">
                    <div class="form-group mx-sm-2 position-relative">
                        <input type="text" name="search_keywords" id="search_keywords" class="form-control" style="width: 560px;" placeholder="Multi Search By Column Dipisah Dengan Separator Koma (,) Tanpa Spasi eg:A,B,C" value="{{ request()->get('search_keywords') }}">
                        <span id="clear-search_keywords" class="clear-text" style="display: none;">&times;</span>
                    </div>
                    <div class="form-group mx-sm-2">
                        <select name="site_situs" class="form-control">
                            <option value="">Pilih Situs</option>
                            @foreach(\App\Models\Daftarsitus::all() as $site)
                                <option value="{{ $site->nama_situs }}" {{ request()->get('site_situs') == $site->nama_situs ? 'selected' : '' }}>{{ $site->nama_situs }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mx-sm-2">
                        <select name="periode" class="form-control">
                            <option value="">Pilih Periode</option>
                            @foreach (\App\Models\SettingPeriode::all() as $periode)
                                <option value="{{ $periode->tahun . $periode->periode }}"
                                    {{ request()->get('periode') == $periode->tahun . $periode->periode ? 'selected' : '' }}>
                                    {{ $periode->bulan_dari . ' s/d ' . $periode->bulan_ke }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Cari</button>
                </form>
            </div>
            {!! $html->table() !!}
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->

    <!-- Modal Generate Report -->
    <div class="modal fade" id="generateReportModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalLabel">Generate Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="generateReportForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tahunSelect">Tahun :</label>
                                    <select class="form-control" id="tahunSelect">
                                        <option value="" disabled selected>Silahkan pilih tahun</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="periodeSelect">Periode :</label>
                                    <select class="form-control" id="periodeSelect" disabled>
                                        <option value="" disabled selected>Silahkan pilih periode</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bulanDari">Bulan Dari :</label>
                                    <input type="text" class="form-control" id="bulanDari" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bulanKe">Bulan Ke :</label>
                                    <input type="text" class="form-control" id="bulanKe" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="button" class="btn btn-success" onclick="runGenerate()">Generate</button>
                            <button type="button" class="btn btn-danger float-right" data-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Export Report -->
    <div class="modal fade" id="exportReportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-secondary">
                    <h5 class="modal-title" id="exportModalLabel">Export Report</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="exportReportForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="exportTahunSelect">Tahun :</label>
                                    <select class="form-control" id="exportTahunSelect">
                                        <option value="" disabled selected>Silahkan pilih tahun</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="exportPeriodeSelect">Periode :</label>
                                    <select class="form-control" id="exportPeriodeSelect" disabled>
                                        <option value="" disabled selected>Silahkan pilih periode</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="exportBulanDari">Bulan Dari :</label>
                                    <input type="text" class="form-control" id="exportBulanDari" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="exportBulanKe">Bulan Ke :</label>
                                    <input type="text" class="form-control" id="exportBulanKe" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="button" class="btn btn-success" onclick="runExport()">Export</button>
                            <button type="button" class="btn btn-danger float-right"
                                data-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
    {!! $html->scripts() !!}
    <script>
        $(document).ready(function() {

            if ($('#search_keywords').val()) {
                $('#clear-search_keywords').show();
            } else {
                $('#clear-search_keywords').hide();
            }

            $('#search_keywords').on('input', function() {
                if ($(this).val()) {
                    $('#clear-search_keywords').show();
                } else {
                    $('#clear-search_keywords').hide();
                }
            });

            $('#clear-search_keywords').on('click', function() {
                $('#search_keywords').val('').trigger('input');
                $('#searchForm').submit();
            });

            $('#searchForm input, #searchForm select').on('change', function() {
                $('#searchForm').submit();
            });

            // Generate Report Logic
            $.get('/reporting/get-tahun', function(data) {
                $('#tahunSelect').empty().append(
                    '<option value="" disabled selected>Silahkan pilih tahun</option>');
                $.each(data, function(index, tahun) {
                    $('#tahunSelect').append('<option value="' + tahun.tahun + '">' + tahun.tahun +
                        '</option>');
                });
            });

            $('#tahunSelect').change(function() {
                var tahun = $(this).val();
                $('#periodeSelect').prop('disabled', false).empty().append(
                    '<option value="" disabled selected>Silahkan pilih periode</option>');
                $.get('/reporting/get-periode/' + tahun, function(data) {
                    if (data.length > 0) {
                        $.each(data, function(index, periode) {
                            $('#periodeSelect').append($('<option>', {
                                value: periode.periode,
                                text: periode.periode,
                                'data-bulan-dari': periode.bulan_dari,
                                'data-bulan-ke': periode.bulan_ke
                            }));
                        });
                    } else {
                        $('#periodeSelect').prop('disabled', true);
                        $('#bulanDari').val('');
                        $('#bulanKe').val('');
                    }
                });
            });

            $('#periodeSelect').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var bulanDari = selectedOption.data('bulan-dari');
                var bulanKe = selectedOption.data('bulan-ke');
                $('#bulanDari').val(bulanDari);
                $('#bulanKe').val(bulanKe);
            });

            window.runGenerate = function() {
                var tahun = $('#tahunSelect').val();
                var periode = $('#periodeSelect').val();

                if (!tahun || !periode) {
                    Swal.fire('Error', 'Silakan pilih tahun dan periode terlebih dahulu.', 'error');
                    return;
                }

                var formData = {
                    tahun: tahun,
                    periode: periode,
                    _token: '{{ csrf_token() }}'
                };

                $.post('/reporting/generate-report', formData, function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.value) {
                                window.location.reload();
                            }
                        });
                        $('#generateReportModal').modal('hide');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    Swal.fire('Failed', 'Gagal melakukan generate report: ' + errorThrown, 'error');
                });
            };

            // Export Report Logic
            $.get('/reporting/get-tahun', function(data) {
                $('#exportTahunSelect').empty().append(
                    '<option value="" disabled selected>Silahkan pilih tahun</option>');
                $.each(data, function(index, tahun) {
                    $('#exportTahunSelect').append('<option value="' + tahun.tahun + '">' + tahun
                        .tahun + '</option>');
                });
            });

            $('#exportTahunSelect').change(function() {
                var tahun = $(this).val();
                $('#exportPeriodeSelect').prop('disabled', false).empty().append(
                    '<option value="" disabled selected>Silahkan pilih periode</option>');
                $.get('/reporting/get-periode/' + tahun, function(data) {
                    if (data.length > 0) {
                        $.each(data, function(index, periode) {
                            $('#exportPeriodeSelect').append($('<option>', {
                                value: periode.periode,
                                text: periode.periode,
                                'data-bulan-dari': periode.bulan_dari,
                                'data-bulan-ke': periode.bulan_ke
                            }));
                        });
                    } else {
                        $('#exportPeriodeSelect').prop('disabled', true);
                    }
                });
            });

            $('#exportPeriodeSelect').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var bulanDari = selectedOption.data('bulan-dari');
                var bulanKe = selectedOption.data('bulan-ke');
                $('#exportBulanDari').val(bulanDari);
                $('#exportBulanKe').val(bulanKe);
            });

            window.runExport = function() {
                var tahun = $('#exportTahunSelect').val();
                var periode = $('#exportPeriodeSelect').val();
                var bulanDari = $('#exportBulanDari').val();
                var bulanKe = $('#exportBulanKe').val();

                if (!tahun || !periode) {
                    Swal.fire('Error', 'Silakan pilih tahun dan periode terlebih dahulu.', 'error');
                    return;
                }

                var url = '/reporting/export?tahun=' + tahun + '&periode=' + periode + '&bulan_dari=' +
                    bulanDari + '&bulan_ke=' + bulanKe;

                $.get(url, function(response) {
                    if (response.success === false) {
                        Swal.fire('Error', response.message, 'error');
                    } else {
                        window.location.href = url;
                    }
                }).fail(function() {
                    Swal.fire('Failed', 'Data tidak ditemukan!', 'error');
                });
            };
        });
    </script>
@endpush
