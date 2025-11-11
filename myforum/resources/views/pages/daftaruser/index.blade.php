@extends('layouts.app')
@section('title','Daftar Staff')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item active">Daftar Staff</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Data Staff</h3>
            <div class="float-right">
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#assignPrivilege">Assign Menu</button>
                <a href="{{ route('daftaruser.new') }}" class="btn btn-primary">Tambahkan Staff Baru</a>
            </div>
        </div>
        <div class="card-body">
            {!! $html->table() !!}
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
    <!-- Assign Users -->
    <div class="modal fade" id="assignPrivilege" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
        {{-- <div> --}}
           <div class="modal-dialog" role="document">
               <form method="POST" action="{{ route('daftaruser.privilege') }}" id="formPrivilege" enctype="multipart/form-data">
                   <div class="modal-content">
                       <div class="modal-header">
                           <h5 class="modal-title" id="ModalLabel">Assign Menu</h5>
                       </div>
                       <div class="modal-body">
                           {{ csrf_field() }}
                           <div class="form-group">
                               {{-- <label for="">Pilih ID Admin <span>*</span></label>
                               {!! Form::select('', $users_privilege, null, [
                                   'class' => 'form-control',
                                   'id' => 'users_privilege',
                                   'placeholder' => 'Silakan pilih ID Admin',
                               ]) !!} --}}
                               <input class="form-control" list="datalistOptions" id="users_privilege" name="users_privilege" placeholder="Diketik dan Pilih Nama ID Admin">
                               <datalist id="datalistOptions">
                                   @foreach ($users_privilege as $id => $datastaff)
                                       <option value="{{ $id }}">{{ $datastaff }}</option>
                                   @endforeach
                               </datalist>
                           </div>
                           <div class="form-group">
                                   {!! Form::hidden('id_admin', null, ['class' => 'form-control', 'id' => 'id_admin', 'required', 'readonly']) !!}</label>
                           </div>
                           <div class="row">
                               <div class="col-md-12">
                                   <div class="content-box">
                                       <table class="table table-striped table-bordered">
                                       <!-- Loop menu -->
                                           <tr class="info">
                                               <td>Deskripsi Menu</td>
                                               <td>Pilih</td>
                                           </tr>
                                           @foreach ($daftarmenu as $menu)
                                               <tr class="treegrid-<?= $menu->menu_id; ?>">
                                                   <td><?= $menu->menu_deskripsi; ?></td>
                                                   <td><input type="checkbox" name="menu[]" id="checkbox-{{ $menu->menu_id }}" value="{{ $menu->menu_id }}"></td>
                                               </tr>
                                           @endforeach
                                       </table>
                                   </div>
                               </div>
                           </div>
                       </div>
                       <div class="modal-footer">
                           <button class="btn btn-primary" onclick="form_submit()" data-dismiss="modal">Simpan</button>
                           <button class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                       </div>
                   </div>
               </form>
           </div>
       </div>
@endsection
@push('scripts')
    {!! $html->scripts() !!}
    <script type="text/javascript">
        $('#users_privilege').change(function() {
            var users_privilege = $(this).val();
            $("#id_admin").val(users_privilege);

                $("input[type='checkbox']").each(function() {
                    $(this).prop('checked', false);
                });

                if (users_privilege !== '') {
                    var url = '{{ route('daftaruser.load', ':id') }}';
                    url = url.replace(':id', users_privilege);
                    $.ajax({
                        url: url,
                        type: 'get',
                        dataType: 'json',
                        success: function(response) {
                            if (response !== null && response !== "Undefined") {
                                for (var i = 0; i < response.length; i++) {
                                $("#checkbox-" + response[i].menu_id).prop('checked', true);
                                }
                            }
                        }
                    });
                }
        });
    </script>
    <script type="text/javascript">
        function form_submit() {
            document.getElementById("formPrivilege").submit();
         }
    </script>
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
                            url: "{{ route('daftaruser.destroy') }}",
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
