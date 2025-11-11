@extends('layouts.app')
@section('title','HS Forum (Data Lama)')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item active">HS Forum (Data Lama)</li>
    </ol>
@endsection
@section('content')
    {{-- <div class="container-fluid"> --}}
        <!-- Default box -->
        <div class="card table-responsive">
            <div class="card-header">
                <h3 class="card-title">Hello, <strong>{!! Auth::user()->id_admin; !!} / {!! Auth::user()->nama_staff; !!}</strong></h3>
            </div>
            <div class="card-body mt-3" style="min-height: 760px">
                <!-- /.card-body -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                @if($case_closed_aman > 1 || !empty($case_closed_aman))
                                    <h3>{{ $case_closed_aman }}</h3>
                                    <p>CLOSED CASES (TIDAK BERSALAH)</p>
                                @else
                                    <h3>0</h3>
                                    <p>CLOSED CASE (TIDAK BERSALAH)</p>
                                @endif
                            </div>
                            <div class="icon">
                            <i class="ion ion-happy"></i>
                            </div>
                            <a href="{{ route('olddata.olddatanoguilt') }}" class="small-box-footer">Info Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-success">
                            <div class="inner">
                                @if($case_closed_bersalah_low > 1 || !empty($case_closed_bersalah_low))
                                    <h3>{{ $case_closed_bersalah_low }}</h3>
                                    <p>CLOSED CASES KESALAHAN (LOW)</p>
                                @else
                                    <h3>0</h3>
                                    <p>CLOSED CASE KESALAHAN (LOW)</p>
                                @endif
                            </div>
                            <div class="icon">
                            <i class="ion ion-minus-circled"></i>
                            </div>
                            <a href="{{ route('olddata.olddatalowguilt') }}" class="small-box-footer">Info Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                @if($case_closed_bersalah_medium > 1 || !empty($case_closed_bersalah_medium))
                                    <h3>{{ $case_closed_bersalah_medium }}</h3>
                                    <p>CLOSED CASES KESALAHAN (MEDIUM)</p>
                                @else
                                    <h3>0</h3>
                                    <p>CLOSED CASE KESALAHAN (MEDIUM)</p>
                                @endif
                            </div>
                            <div class="icon">
                            <i class="ion ion-minus-circled"></i>
                            </div>
                            <a href="{{ route('olddata.olddatamediumguilt') }}" class="small-box-footer">Info Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-danger">
                            <div class="inner">
                                @if($case_closed_bersalah_high > 1 || !empty($case_closed_bersalah_high))
                                    <h3>{{ $case_closed_bersalah_high }}</h3>
                                    <p>CLOSED CASES KESALAHAN (HIGH)</p>
                                @else
                                    <h3>0</h3>
                                    <p>CLOSED CASE KESALAHAN (HIGH)</p>
                                @endif
                            </div>
                            <div class="icon">
                            <i class="ion ion-minus-circled"></i>
                            </div>
                            <a href="{{ route('olddata.olddatahighguilt') }}" class="small-box-footer">Info Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.card -->
        </div>
    {{-- </div> --}}
@endsection
