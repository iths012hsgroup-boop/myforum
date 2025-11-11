@extends('layouts.app')
@section('title','Settings Periode')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('periodessetting.setting') }}">Setting Periode</a></li>
        <li class="breadcrumb-item active">Form Tambah</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Form Tambah Periode</h3>
        </div>

        {{ html()->form('POST', '/periodessetting/save')->open() }}
        <div class="card-body">
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Bulan Dari :</label>
                    <div class="col-sm-6">
                        <input type="text" class="date-picker form-control" placeholder="Diisi Bulan Dari(Y-m-d H:i:s)" name="bulan_dari" id="bulan_dari" required value={{ old('bulan_dari') }}>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Bulan Ke :</label>
                    <div class="col-sm-6">
                        <input type="text" class="date-picker form-control" placeholder="Diisi Bulan Ke(Y-m-d H:i:s)" name="bulan_ke" id="bulan_ke" required value={{ old('bulan_ke') }}>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Tahun :</label>
                    <div class="col-sm-6">
                        <input type="text" class="year-picker form-control" placeholder="Pilih Tahun" name="tahun" id="tahun" required value="{{ old('tahun') }}">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Periode :</label>
                    <div class="col-sm-6">
                        <select class="form-control" name="periode" id="periode" required>
                            <option value="1">1</option>
                            <option value="2">2</option>
                        </select>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('periodessetting.setting') }}" class="btn btn-danger">Batal</a>
        </div>
        <!-- /.card-body -->
        {{ html()->form()->close() }}
    </div>
    <!-- /.card -->
@endsection
@push('scripts')
<script>
    $(document).ready(function(){

        $("#bulan_dari").datepicker({
            format: 'yyyy-mm-dd',
            todayHighlight: true,
            autoclose: true,
            orientation: 'bottom'
        }).on('changeDate', function(e) {

            setTimeout(function() {

                var selectedDate = e.format(0, "yyyy-mm-dd");
                $('#bulan_dari').val(selectedDate + ' 00:00:00');
            }, 100);
        });

        $("#bulan_ke").datepicker({
            format: 'yyyy-mm-dd',
            todayHighlight: true,
            autoclose: true,
            orientation: 'bottom'
        }).on('changeDate', function(e) {

            setTimeout(function() {

                var selectedDate = e.format(0, "yyyy-mm-dd");
                $('#bulan_ke').val(selectedDate + ' 23:59:59');
            }, 100);
        });

        $(".year-picker").datepicker({
            format: "yyyy",
            viewMode: "years", 
            minViewMode: "years",
            autoclose: true,
            orientation: 'bottom'
        });
    });
</script>
@endpush

