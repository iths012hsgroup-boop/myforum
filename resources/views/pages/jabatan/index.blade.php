@extends('layouts.app')
@section('title','Daftar Jabatan')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item active">Daftar Jabatan</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Data Jabatan</h3>
            <div class="float-right">
                <a href="{{ route('daftarjabatan.new') }}" class="btn btn-primary">Tambahkan Jabatan Baru</a>
            </div>
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
        $(document).ready(function(){
            $('table#dataTableBuilder tbody').on( 'click', 'td button', function (e) {
                var mode = $(this).attr("data-mode");
                var parent = $(this).parent().get( 0 );
                var parent1 = $(parent).parent().get( 0 );
                var row = $('#dataTableBuilder').DataTable().row(parent1);
                var data = row.data();

                Swal.fire({
                title: 'Apakah anda yakin?',
                text: "Anda tidak akan dapat mengembalikan ini!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!'
                }).then((result) => {
                    if (result.value) {
                        $.ajax({
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-Token' : "{{ csrf_token() }}"
                            },
                            url: "{{ route('daftarjabatan.destroy') }}",
                            dataType: 'JSON',
                            cache: false,
                            data: data,
                            success: function(result) {
                                toastr.success(result.message, 'Success !')
                                $('#dataTableBuilder').DataTable().ajax.reload();
                            },
                            error: function(err){
                                toastr.error("Gagal hapus! silakan hubungi Administrator",'Error !')
                            }
                        });
                    }
                })
            })
        })
    </script>
@endpush
