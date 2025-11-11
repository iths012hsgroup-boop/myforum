@extends('layouts.app')
@section('title','Setting Situs')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item active">Setting Situs</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Pengaturan</h3>
        </div>
        <div class="card-body table-responsive">
            @if (!empty($settings->id))
                {{ html()->form('POST', '/setting/update')->open() }}
                <div class="card-body">

                    <div class="form-group">
                        <input type="hidden" class="form-control" name="id" value="{{ $settings->id }}">
                    </div>

                    <div class="form-group">
                        <label for="">Pengumuman <span>*</span></label>
                        <textarea class="form-control" placeholder="Diisi dengan kalimat pengumuman" name="pengumuman" id="pengumuman" required>{{ $settings->pengumuman }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
                <!-- /.card-body -->
                {{ html()->form()->close() }}
            @else
                {{ html()->form('POST', '/setting/save')->open() }}
                <div class="card-body">

                    <div class="form-group">
                        <label for="">Pengumuman <span>*</span></label>
                        <textarea class="form-control" placeholder="Diisi dengan kalimat pengumuman" name="pengumuman" id="pengumuman" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
                <!-- /.card-body -->
                {{ html()->form()->close() }}
            @endif
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
@endsection
