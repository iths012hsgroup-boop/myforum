@extends('layouts.app')
@section('title','Ubah Password')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item active">Ganti Password</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Form Edit Password</h3>
        </div>

        {{ html()->form('POST', '/daftaruser/updatepassword')->open() }}
        <div class="card-body">

            @if (session('status'))
            <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
            @elseif (session('error'))
                <div class="alert alert-danger" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-3">
                <label for="oldPasswordInput" class="form-label">Password Lama</label>
                <input name="old_password" type="password" class="form-control @error('old_password') is-invalid @enderror" id="oldPasswordInput"
                    placeholder="Password Lama" autocomplete="off">
                @error('old_password')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-3">
                <label for="newPasswordInput" class="form-label">Password Baru</label>
                <input name="new_password" type="password" class="form-control @error('new_password') is-invalid @enderror" id="newPasswordInput"
                    placeholder="Password Baru" autocomplete="off">
                @error('new_password')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-3">
                <label for="confirmNewPasswordInput" class="form-label">Konfirmasi Password Baru</label>
                <input name="new_password_confirmation" type="password" class="form-control" id="confirmNewPasswordInput"
                    placeholder="Konfirmasi Password Baru" autocomplete="off">
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('dashboard.index')  }}" class="btn btn-danger">Batal</a>
        </div>
        <!-- /.card-body -->
        {{ html()->form()->close() }}
    </div>
    <!-- /.card -->
@endsection
