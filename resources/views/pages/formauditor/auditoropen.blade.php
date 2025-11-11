@extends('layouts.app')
@section('title','HS Forum (Auditor)')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('auditorforum.new') }}">HS Forum (Auditor)</a></li>
        <li class="breadcrumb-item active">Forum - Open Cases</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Data Open Cases</h3>
        </div>
        <div class="card-body">
            {!! $html->table() !!}
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
@endsection
@push('scripts')
    {!! $html->scripts() !!}
@endpush
