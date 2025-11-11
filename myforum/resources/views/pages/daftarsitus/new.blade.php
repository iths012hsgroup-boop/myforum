@extends('layouts.app')
@section('title','Daftar Situs')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('daftarsitus.index') }}">Data Daftar Situs</a></li>
        <li class="breadcrumb-item active">Form Tambah</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Form Tambah Situs Baru</h3>
        </div>
        {{ html()->form('POST', '/daftarsitus/save')->open() }}
        <div class="card-body">
            <div class="form-group">
                <label for="">Nama Situs <span>*</span></label>
                <input type="text" class="form-control" placeholder="Diisi dengan nama situs" name="nama_situs" id="nama_situs" required>
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('daftarsitus.index')  }}" class="btn btn-danger">Batal</a>
        </div>
        <!-- /.card-body -->
        {{ html()->form()->close() }}
    </div>
    <!-- /.card -->
@endsection
