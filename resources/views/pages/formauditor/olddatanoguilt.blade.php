@extends('layouts.app')
@section('title','HS Forum (Data Lama)')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('olddata.olddataclosedcases') }}">HS Forum (Data Lama)</a></li>
        <li class="breadcrumb-item active">Forum - Closed Cases (Tidak Bersalah)</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <div class="float-right">
                <form id="searchForm" action="{{ route('olddata.olddatanoguilt') }}" method="GET" class="form-inline">
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
                    <button type="submit" class="btn btn-primary">Cari</button>
                </form>
            </div>
            <h3 class="card-title">Data Closed Cases (Tidak Bersalah)</h3>
        </div>
        <div class="card-body">
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
            $('#searchForm input, #searchForm select').on('change', function() {
                $('#searchForm').submit();
            });
        });
    </script>
@endpush
