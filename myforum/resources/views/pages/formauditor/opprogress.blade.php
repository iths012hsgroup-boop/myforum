@extends('layouts.app')
@section('title','HS Forum (OP)')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('opforum.indexop') }}">HS Forum (OP)</a></li>
        <li class="breadcrumb-item active">Forum - On Progress Cases</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Data On Progress Cases</h3>
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
