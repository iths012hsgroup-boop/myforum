@extends('layouts.app')
@section('title','Daftar Staff')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('daftaruser.index') }}">Data Daftar Staff</a></li>
        <li class="breadcrumb-item active">Form Tambah</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Form Tambah Staff Baru</h3>
        </div>

        {{ html()->form('POST', '/daftaruser/save')->open() }}
        <div class="card-body">
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">ID Admin :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" placeholder="Diisi dengan ID Admin" name="id_admin" id="id_admin" required value={{ old('id_admin') }}>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Password :</label>
                    <div class="col-sm-6">
                        <input type="password" class="form-control" placeholder="Diisi dengan Password" name="password" id="password" required>
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
                                <option value="{{ $id }}">{{ $listsitus }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div> --}}
            <div class="form-group">
                <div class="row">
                    <label for="id_situs" class="col-sm-3 col-form-label">Akses Situs :</label>
                    <div class="col-sm-6">
                        <select name="id_situs[]" id="id_situs" class="select2bs4 w-100" multiple="multiple" data-placeholder="Pilih Akses Situs">
                            @foreach ($listsituss as $id => $listsitus)
                                <option value="{{ $id }}" {{ in_array($id, old('id_situs', [])) ? 'selected' : '' }}>
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
                        <input type="text" class="form-control" placeholder="Diisi dengan Nama Lengkap Staff" name="nama_staff" id="nama_staff" required value={{ old('nama_staff') }}>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Alamat Email :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" placeholder="Diisi dengan Alamat Email Kerja" name="email" id="email" required value={{ old('email') }}>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Nomor Paspor :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" placeholder="Diisi dengan Nomor Paspor" name="nomor_paspor" id="nomor_paspor" required value={{ old('nomor_paspor') }}>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Masa Aktif Paspor :</label>
                    <div class="col-sm-6">
                        <input type="text" class="date-picker form-control" placeholder="Diisi dengan Masa Aktif Paspor(YYYY-MM-DD)" name="masa_aktif_paspor" id="masa_aktif_paspor" required value={{ old('masa_aktif_paspor') }}>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Nomor Visa :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" placeholder="Diisi dengan Nomor Visa" name="nomor_visa" id="nomor_visa" required value={{ old('nomor_visa') }}>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Masa Aktif Visa :</label>
                    <div class="col-sm-6">
                        <input type="text" class="date-picker form-control" placeholder="Diisi dengan Masa Aktif Visa(YYYY-MM-DD)" name="masa_aktif_visa" id="masa_aktif_visa" required value={{ old('masa_aktif_visa') }}>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Tanggal Bergabung :</label>
                    <div class="col-sm-6">
                        <input type="text" class="date-picker form-control" placeholder="Diisi dengan Tanggal Bergabung(YYYY-MM-DD)" name="tanggal_join" id="tanggal_join" required value={{ old('tanggal_join') }}>
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
                                <option value="{{ $id }}">{{ $position }}</option>
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
                            <option value=""> -- Silakan Pilih Salah Satu --</option>
                            {{-- @foreach ($jabatans as $id => $jabatan)
                                <option value="{{ $id }}">{{ $jabatan }}</option>
                            @endforeach --}}
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
                            <option value="0">Tidak Aktif</option>
                            <option value="1">Aktif</option>
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
    })

    $('.select2').select2()

    $('.select2bs4').select2({
        theme: 'bootstrap4'
    })
</script>
<script>
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
