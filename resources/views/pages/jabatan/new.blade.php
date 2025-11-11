@extends('layouts.app')
@section('title','Daftar Jabatan')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('daftarjabatan.index') }}">Data Daftar Jabatan</a></li>
        <li class="breadcrumb-item active">Form Tambah</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Form Tambah Jabatan Baru</h3>
        </div>

        {{ html()->form('POST', '/daftarjabatan/save')->open() }}
        <div class="card-body">
            <div class="form-group">
                <label for="">Posisi Jabatan <span>*</span></label>
                <select name="bagian_posisi" id="bagian_posisi" class="form-control" placeholder="Dipilih Posisi Kerja" required>
                    <option value=""> -- Silakan Pilih Salah Satu --</option>
                    @foreach ($positions as $id => $position)
                        <option value="{{ $id }}">{{ $position }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="">Nama Jabatan <span>*</span></label>
                <input type="text" class="form-control" placeholder="Diisi dengan nama Jabatan" name="nama_jabatan" id="nama_jabatan" required>
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('daftarjabatan.index')  }}" class="btn btn-danger">Batal</a>
        </div>
        <!-- /.card-body -->
        {{ html()->form()->close() }}
    </div>
    <!-- /.card -->
@endsection
