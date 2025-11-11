@extends('layouts.app')
@section('title','Daftar Staff')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('daftaruser.index') }}">Data Daftar Staff</a></li>
        <li class="breadcrumb-item active">Form Edit</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Form Edit Staff Baru</h3>
        </div>

        {{ html()->form('POST', '/daftaruser/update')->open() }}
        <div class="card-body">
            <div class="form-group">
                <input type="hidden" class="form-control" name="id" value="{{ $datastaff->id }}">
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">ID Admin :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" placeholder="Diisi dengan ID Admin" name="id_admin" id="id_admin" value="{{ $datastaff->id_admin }}" required>
                    </div>
                </div>
            </div>
            {{-- <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Akses Situs :</label>
                    <div class="col-sm-6">
                        <select name="id_situs" id="id_situs" class="form-control" placeholder="Dipilih Akses Situs" required>
                            <option value=""> -- Silakan Pilih Salah Satu --</option>
                            @foreach ($listsituss as $id => $listsitus)
                                <option value="{{ $id }}" {{ ($datastaff->id_situs == $id) ? 'selected' : '' }}>{{ $listsitus }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div> --}}
            @php
                $selectedSitus = explode(',', $datastaff->id_situs ?? '');
            @endphp
            <div class="form-group">
                <div class="row">
                    <label for="id_situs" class="col-sm-3 col-form-label">Akses Situs :</label>
                    <div class="col-sm-6">
                        <select name="id_situs[]" id="id_situs" class="select2bs4 w-100" multiple="multiple" data-placeholder="Pilih Akses Situs">
                            @foreach ($listsituss as $id => $listsitus)
                                <option value="{{ $id }}" {{ in_array($id, $selectedSitus) ? 'selected' : '' }}>
                                    {{ $listsitus }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Nama Staff :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" placeholder="Diisi dengan Nama Lengkap Staff" name="nama_staff" id="nama_staff" value="{{ $datastaff->nama_staff }}" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Alamat Email :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" placeholder="Diisi dengan Alamat Email Kerja" name="email" id="email" value="{{ $datastaff->email }}" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Nomor Paspor :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" placeholder="Diisi dengan Nomor Paspor" name="nomor_paspor" id="nomor_paspor" value="{{ $datastaff->nomor_paspor }}" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Masa Aktif Paspor :</label>
                    <div class="col-sm-6">
                        <input type="text" class="date-picker form-control" placeholder="Diisi dengan Masa Aktif Paspor(YYYY-MM-DD)" name="masa_aktif_paspor" id="masa_aktif_paspor" value="{{ $datastaff->masa_aktif_paspor }}" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Nomor Visa :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" placeholder="Diisi dengan Nomor Visa" name="nomor_visa" id="nomor_visa" value="{{ $datastaff->nomor_visa }}" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Masa Aktif Visa :</label>
                    <div class="col-sm-6">
                        <input type="text" class="date-picker form-control" placeholder="Diisi dengan Masa Aktif Visa(YYYY-MM-DD)" name="masa_aktif_visa" id="masa_aktif_visa" value="{{ $datastaff->masa_aktif_visa }}" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Tanggal Bergabung :</label>
                    <div class="col-sm-6">
                        <input type="text" class="date-picker form-control" placeholder="Diisi dengan Tanggal Bergabung(YYYY-MM-DD)" name="tanggal_join" id="tanggal_join" value="{{ $datastaff->tanggal_join }}" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Posisi Kerja :</label>
                    <div class="col-sm-6">
                        <select name="posisi_kerja" id="posisi_kerja" class="form-control" placeholder="Dipilih Posisi Kerja" required>
                            <option value=""> -- Silakan Pilih Salah Satu --</option>
                            @foreach ($positions as $id => $position)
                                <option value="{{ $id }}" {{ ($datastaff->posisi_kerja == $id) ? 'selected' : '' }}>{{ $position }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Posisi Jabatan :</label>
                    <div class="col-sm-6">
                        <select name="id_jabatan" id="id_jabatan" class="form-control" placeholder="Dipilih Jabatan Kerja" required>
                            <option value="{{ $datastaff->id_jabatan }}"></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Status :</label>
                    <div class="col-sm-6">
                        <select name="status" id="status" class="form-control" placeholder="Dipilih Status Staff" required>
                            <option value=""> -- Silakan Pilih Salah Satu --</option>
                            <option value="0" {{ ($datastaff->status == 0) ? 'selected' : '' }}>Tidak Aktif</option>
                            <option value="1" {{ ($datastaff->status == 1) ? 'selected' : '' }}>Aktif</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('daftaruser.index')  }}" class="btn btn-danger">Batal</a>

        </div>
        <!-- /.card-body -->
        {{ html()->form()->close() }}
    </div>
    <!-- /.card -->
@endsection
@push('scripts')
<script>
    $(document).ready(function(){
        $(".date-picker").datepicker({
            format: 'yyyy-mm-dd',
            todayHighlight: true
        });
    });

    $('.select2').select2()

    $('.select2bs4').select2({
        theme: 'bootstrap4'
    })
</script>
<script type="text/javascript">
     $(document).ready(function(){
        const id = document.getElementById("id_jabatan");
        var url = '{{ route('other.getjabatan', ':id') }}';
        url = url.replace(':id', id.value);

        $.ajax({
            url: url,
            type: 'get',
            dataType: 'json',
            success: function(response) {
                if (response !== null) {
                    $('#id_jabatan').html('<option value="'+response.id+'">'+response.nama_jabatan+'</option>');
                }
            }
        });
    });

    $('#posisi_kerja').change(function(){
        var id = $(this).val();
        var url = '{{ route('other.getposisijabatan', ':id') }}';
        url = url.replace(':id', id);

        $.ajax({
            url: url,
            type: 'get',
            dataType: 'json',
            success: function(response) {
                if (response !== null) {
                    $('#id_jabatan').html('<option selected="selected" value="">-- Silakan Pilih Salah Satu --</option>');
                    $.each(response, function(key, value) {
                        $('#id_jabatan').append('<option value="'+value.id+'">'+value.nama_jabatan+'</option>');
                    });
                }
            }
        });
    });
</script>
@endpush
