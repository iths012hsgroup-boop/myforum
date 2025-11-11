@extends('layouts.app')
@section('title','Reset Password')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item active">Reset Password</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Form Reset Password</h3>
        </div>

        {{ html()->form('POST', '/resetpassword/resetpassword')->open() }}
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
            <div class="form-group">
                <input class="form-control" list="datalistOptions" id="userid" name="userid" placeholder="Diketik dan Pilih Nama ID Admin" required>
                <datalist id="datalistOptions">
                    @foreach ($users as $id => $user)
                        <option value="{{ $id }}">{{ $user }}</option>
                    @endforeach
                </datalist>
            </div>
            <div class="mb-3">
                <label for="newPasswordInput" class="form-label">Password Baru</label>
                <input name="new_password" type="password" class="form-control @error('new_password') is-invalid @enderror" id="newPasswordInput" placeholder="Password Baru" autocomplete="off" required>
                @error('new_password')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-3">
                <label for="confirmNewPasswordInput" class="form-label">Konfirmasi Password Baru</label>
                <input name="new_password_confirmation" type="password" class="form-control" id="confirmNewPasswordInput" placeholder="Konfirmasi Password Baru" autocomplete="off" required>
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
            <a href="{{ route('dashboard.index')  }}" class="btn btn-danger">Batal</a>
        </div>
        <!-- /.card-body -->
        {{ html()->form()->close() }}
    </div>
    <!-- /.card -->
@endsection
