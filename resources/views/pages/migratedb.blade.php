@extends('layouts.app')
@section('title','Migrasi Database')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item active">Migrasi Database ID Admin</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Migrasi Database ID Admin</h3>
        </div>
        {{ html()->form('POST', '/migrasidb/update')->open() }}
            <div class="card-body table-responsive">
                <div class="row">
                    <div class="col-lg-6 col-6">
                        <div class="form-group">
                            <label for="idadmin_dari">ID Admin Dari<span>*</span></label>
                            <input class="form-control" list="datalistOptions" id="idadmin_dari" name="idadmin_dari" placeholder="Diketik dan Pilih Nama ID Admin Dari" required>
                            <datalist id="datalistOptions">
                                @foreach ($datastaffs as $id => $datastaff)
                                    <option value="{{ $id }}">{{ $datastaff }}</option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                    <div class="col-lg-6 col-6">
                        <div class="form-group">
                            <label for="idadmin_ke">ID Admin Tujuan<span>*</span></label>
                            <input class="form-control" list="datalistOptions" id="idadmin_ke" name="idadmin_ke" placeholder="Diketik dan Pilih Nama ID Admin Tujuan" required>
                            <datalist id="datalistOptions">
                                @foreach ($datastaffs as $id => $datastaff)
                                    <option value="{{ $id }}">{{ $datastaff }}</option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" onclick="return confirm('Apa Anda Yakin?')">Submit</button>
            </div>
        <!-- /.card-body -->
        {{ html()->form()->close() }}
    </div>
    <!-- /.card -->
        <!-- Default box -->
        <div class="card table-responsive">
            <div class="card-header">
                <h3 class="card-title">Migrasi Database Auditor</h3>
            </div>
            {{ html()->form('POST', '/migrasidb/updateauditor')->open() }}
                <div class="card-body table-responsive">
                    <div class="row">
                        <div class="col-lg-6 col-6">
                            <div class="form-group">
                                <label for="idauditor_dari">ID Auditor Dari<span>*</span></label>
                                <input class="form-control" list="datalistAuditor" id="idauditor_dari" name="idauditor_dari" placeholder="Diketik dan Pilih Nama ID Auditor Dari" required>
                                <datalist id="datalistAuditor">
                                    @foreach ($dataauditors as $ida => $dataauditor)
                                        <option value="{{ $ida }}">{{ $dataauditor }}</option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                        <div class="col-lg-6 col-6">
                            <div class="form-group">
                                <label for="idauditor_ke">ID Auditor Tujuan<span>*</span></label>
                                <input class="form-control" list="datalistAuditor" id="idauditor_ke" name="idauditor_ke" placeholder="Diketik dan Pilih Nama ID Auditor Tujuan" required>
                                <datalist id="datalistAuditor">
                                    @foreach ($dataauditors as $ida => $dataauditor)
                                        <option value="{{ $ida }}">{{ $dataauditor }}</option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Apa Anda Yakin?')">Submit</button>
                </div>
            <!-- /.card-body -->
            {{ html()->form()->close() }}
        </div>
        <!-- /.card -->
@endsection

