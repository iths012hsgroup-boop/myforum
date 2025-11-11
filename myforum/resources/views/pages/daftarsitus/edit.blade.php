@extends('layouts.app')
@section('title','Daftar Situs')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="#">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('daftarsitus.index') }}">Data Daftar Situs</a></li>
        <li class="breadcrumb-item active">Form Update</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Form Edit Daftar Situs</h3>
        </div>
        {{ html()->form('POST', '/daftarsitus/update')->open() }}
        <div class="card-body">
            <div class="form-group">
                <input type="hidden" class="form-control" name="id" value="{{ $daftarsitus->id }}">
            </div>
            <div class="form-group">
                <label for="">Nama Situs <span>*</span></label>
                <input type="text" class="form-control" placeholder="Diisi dengan nama situs" name="nama_situs" id="nama_situs" value="{{ $daftarsitus->nama_situs }}">
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('daftarsitus.index')  }}" class="btn btn-default">Batal</a>
        </div>
        <!-- /.card-body -->
        {{ html()->form()->close() }}
    </div>
    <!-- /.card -->
@endsection
