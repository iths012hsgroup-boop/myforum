@extends('layouts.app')
@section('title','Daftar Staff')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('daftaruser.index') }}">Data Daftar Staff</a></li>
        <li class="breadcrumb-item active">Details</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Details Data Staff</h3>
        </div>

        {{ html()->form('POST', '/daftaruser/details')->open() }}
        <div class="card-body">
            <div class="form-group">
                <input type="hidden" class="form-control" name="id" value="{{ $datastaff->id }}">
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">ID Admin :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="id_admin" id="id_admin" value="{{ $datastaff->id_admin }}" readonly>
                    </div>
                </div>
            </div>
            {{-- <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Akses Situs :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="id_situs" id="id_situs" value="{{ $situs->nama_situs }}" readonly>
                    </div>
                </div>
            </div> --}}
            @php
                $idSitusArray = explode(',', $datastaff->id_situs ?? '');
                $namaSitusArray = collect($situs)->only($idSitusArray);
            @endphp

            <div class="form-group">
                <div class="row">
                    <label class="col-sm-3 col-form-label">Akses Situs :</label>
                    <div class="col-sm-6 pt-1">
                        @forelse ($namaSitusArray as $namaSitus)
                            <span class="badge badge-success mr-1 p-2">{{ $namaSitus }}</span>
                        @empty
                            <span class="text-muted">Tidak ada data situs</span>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Nama Staff :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="nama_staff" id="nama_staff" value="{{ $datastaff->nama_staff }}" readonly>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Alamat Email :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="email" id="email" value="{{ $datastaff->email }}" readonly>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Nomor Paspor :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="nomor_paspor" id="nomor_paspor" value="{{ $datastaff->nomor_paspor }}" readonly>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Masa Aktif Paspor :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="masa_aktif_paspor" id="masa_aktif_paspor" value="{{ $datastaff->masa_aktif_paspor }}" readonly>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Nomor Visa :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="nomor_visa" id="nomor_visa" value="{{ $datastaff->nomor_visa }}" readonly>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Masa Aktif Visa :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="masa_aktif_visa" id="masa_aktif_visa" value="{{ $datastaff->masa_aktif_visa }}" readonly>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Tanggal Bergabung :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="tanggal_join" id="tanggal_join" value="{{ $datastaff->tanggal_join }}" readonly>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Posisi Kerja :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="posisi_kerja" id="posisi_kerja" value="{{ $datastaff->posisi_kerja }}" readonly>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Posisi Jabatan :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="id_jabatan" id="id_jabatan" value="{{ $datastaff->id_jabatan }}" readonly>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="colFormLabel" class="col-sm-3 col-form-label">Status :</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="status" id="status" value="{{ $datastaff->status }}" readonly>
                    </div>
                </div>
            </div>

            <a href="{{ route('daftaruser.index')  }}" class="btn btn-primary">Kembali</a>
        </div>
        <!-- /.card-body -->
        {{ html()->form()->close() }}
    </div>
    <!-- /.card -->
@endsection
@push('scripts')
<script>
    $(document).ready(function() {
        const status_val = document.getElementById("status");
        const posisi_kerja_val = document.getElementById("posisi_kerja");

            if(status_val.value == "1") {
                document.getElementById('status').value = "Aktif";
            } else {
                document.getElementById('status').value = "Tidak Aktif";
            }
            if(posisi_kerja_val.value == "1") {
                document.getElementById('posisi_kerja').value = "Operasional";
            } else if(posisi_kerja_val.value == "2") {
                document.getElementById('posisi_kerja').value = "Marketing";
            } else if(posisi_kerja_val.value == "3") {
                document.getElementById('posisi_kerja').value = "Support";
            }
    });
</script>
<script>
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
                    $('#id_jabatan').val(response.nama_jabatan);
                }
            }
        });
    });
</script>
@endpush
