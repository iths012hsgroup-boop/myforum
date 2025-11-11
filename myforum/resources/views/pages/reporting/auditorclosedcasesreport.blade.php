@extends('layouts.app')
@section('title','HS Forum (Auditor)')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item active">Forum - Closed Cases (Periode Lama)</li>
    </ol>
@endsection
@section('content')
<!-- Default box -->
<div class="card table-responsive">
    <div class="card-header">
        <h3 class="card-title">Data Closed Cases (Periode Lama)</h3>
    </div>
    <div class="card-body">
        <div class="float-right">
            <form id="searchForm" action="{{ route('oldperiode.auditorclosedcasesreport') }}" method="GET" class="form-inline">
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
                        @foreach(\App\Models\SettingPeriode::all() as $periode)
                            <option value="{{ $periode->tahun . $periode->periode }}" {{ request()->get('periode') == $periode->tahun . $periode->periode ? 'selected' : '' }}>
                                {{ $periode->bulan_dari . ' s/d ' . $periode->bulan_ke }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mx-sm-2">
                    <select name="status_kesalahan" class="form-control">
                        <option value="">Pilih Status Kesalahan</option>
                        <option value="1" {{ request()->get('status_kesalahan') == '1' ? 'selected' : '' }}>Tidak Bersalah</option>
                        <option value="2" {{ request()->get('status_kesalahan') == '2' ? 'selected' : '' }}>Bersalah (Low)</option>
                        <option value="3" {{ request()->get('status_kesalahan') == '3' ? 'selected' : '' }}>Bersalah (Medium)</option>
                        <option value="4" {{ request()->get('status_kesalahan') == '4' ? 'selected' : '' }}>Bersalah (High)</option>
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
        });
    </script>
@endpush
