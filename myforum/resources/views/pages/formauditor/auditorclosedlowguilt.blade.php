@extends('layouts.app')
@section('title','HS Forum (Auditor)')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('auditorforum.new') }}">HS Forum (Auditor)</a></li>
        <li class="breadcrumb-item active">Forum - Closed Cases Kesalahan (Low)</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Data Closed Cases Kesalahan (Low)</h3>
        </div>
        <div class="card-body">
            {!! $html->table() !!}
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->

    <div class="modal fade" id="recoveryModal" tabindex="-1" role="dialog" aria-labelledby="recoveryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: #d13737; color: white;">
                    <h5 class="modal-title" id="recoveryModalLabel">Konfirmasi Recovery</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin melakukan recovery untuk data ini?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <form id="recoveryForm" method="POST" action="">
                        @csrf
                        <button type="submit" class="btn btn-warning">Ya, Recovery</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    {!! $html->scripts() !!}

    <script>
        $(document).on('click', '.recovery', function () {
            var url = $(this).data('url'); // Ambil URL dari tombol yang diklik
            $('#recoveryForm').attr('action', url); // Set URL ke form recovery
        });
    </script>
@endpush
