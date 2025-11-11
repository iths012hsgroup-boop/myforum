@extends('layouts.app')
@section('title', 'HS Forum (OP)')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item active">HS Forum (OP)</li>
    </ol>
@endsection
@section('content')
    {{-- <div class="container-fluid"> --}}
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Hello, <strong>{!! Auth::user()->id_admin !!} / {!! Auth::user()->nama_staff !!}</strong></h3>
        </div>

        <div class="card-body" style="min-height:760px">

            {{-- 🔹 NAV TABS --}}
            <ul class="nav nav-tabs mb-3" id="hsforumTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="summary-tab" data-toggle="tab" href="#summary" role="tab"
                        aria-controls="summary" aria-selected="true">
                        <i></i> HS Forum (OP)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="cases-tab" data-toggle="tab" href="#cases" role="tab" aria-controls="cases"
                        aria-selected="false">
                        <i></i> OP ABSENSI
                    </a>
                </li>
            </ul>
            <div class="tab-content" id="hsforumTabsContent">

                <div class="tab-pane fade show active" id="summary" role="tabpanel" aria-labelledby="summary-tab">
                    <!-- /.card-body -->
                    <div class="row">
                        <div class="col-lg-2 col-6">
                            <!-- small box -->
                            <div class="small-box bg-light">
                                <div class="inner">
                                    @if ($case_open > 1 || !empty($case_open))
                                        <h3>{{ $case_open }}</h3>
                                        <p>OPEN CASES</p>
                                    @else
                                        <h3>0</h3>
                                        <p>OPEN CASE</p>
                                    @endif
                                </div>
                                <div class="icon">
                                    <i class="ion ion-pie-graph"></i>
                                </div>
                                <a href="{{ route('opforum.opencases') }}" class="small-box-footer">Info Lebih... <i
                                        class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-2 col-6">
                            <!-- small box -->
                            <div class="small-box bg-fuchsia">
                                <div class="inner">
                                    @if ($case_progress > 1 || !empty($case_progress))
                                        <h3>{{ $case_progress }}</h3>
                                        <p>ON PROGRESS CASES</p>
                                    @else
                                        <h3>0</h3>
                                        <p>ON PROGRESS CASE</p>
                                    @endif
                                </div>
                                <div class="icon">
                                    <i class="ion ion-chatbubbles"></i>
                                </div>
                                <a href="{{ route('opforum.onprogresscases') }}" class="small-box-footer">Info Lebih... <i
                                        class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-2 col-6">
                            <!-- small box -->
                            <div class="small-box bg-indigo">
                                <div class="inner">
                                    @if ($case_progress_aman > 1 || !empty($case_progress_aman))
                                        <h3>{{ $case_progress_aman }}</h3>
                                        <p>ON PROGRESS CASES (TIDAK BERSALAH)</p>
                                    @else
                                        <h3>0</h3>
                                        <p>ON PROGRESS CASE (TIDAK BERSALAH)</p>
                                    @endif
                                </div>
                                <div class="icon">
                                    <i class="ion ion-happy"></i>
                                </div>
                                <a href="{{ route('opforum.onprogressnoguilt') }}" class="small-box-footer">Info Lebih... <i
                                        class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-2 col-6">
                            <!-- small box -->
                            <div class="small-box bg-orange">
                                <div class="inner">
                                    @if ($case_progress_bersalah > 1 || !empty($case_progress_bersalah))
                                        <h3>{{ $case_progress_bersalah }}</h3>
                                        <p>ON PROGRESS CASES (KESALAHAN)</p>
                                    @else
                                        <h3>0</h3>
                                        <p>ON PROGRESS CASE (KESALAHAN)</p>
                                    @endif
                                </div>
                                <div class="icon">
                                    <i class="ion ion-minus-circled"></i>
                                </div>
                                <a href="{{ route('opforum.onprogressguilt') }}" class="small-box-footer">Info Lebih... <i
                                        class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <!-- small box -->
                            <div class="small-box bg-gray">
                                <div class="inner">
                                    @if ($case_pending > 1 || !empty($case_pending))
                                        <h3>{{ $case_pending }}</h3>
                                        <p>PENDING CASES</p>
                                    @else
                                        <h3>0</h3>
                                        <p>PENDING CASE</p>
                                    @endif
                                </div>
                                <div class="icon">
                                    <i class="ion ion-help-circled"></i>
                                </div>
                                <a href="{{ route('opforum.pendingcases') }}" class="small-box-footer">Info Lebih... <i
                                        class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <!-- ./col -->
                        <div class="col-lg-2 col-6">
                            <!-- small box -->
                            <div class="small-box bg-info">
                                <div class="inner">
                                    @if ($case_closed_aman > 1 || !empty($case_closed_aman))
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
                                <a href="{{ route('opforum.closedcasesnoguilt') }}" class="small-box-footer">Info Lebih...
                                    <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- ./col -->
                        <div class="col-lg-2 col-6">
                            <!-- small box -->
                            <div class="small-box bg-success">
                                <div class="inner">
                                    @if ($case_closed_bersalah_low > 1 || !empty($case_closed_bersalah_low))
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
                                <a href="{{ route('opforum.closedcaseslowguilt') }}" class="small-box-footer">Info Lebih...
                                    <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <!-- small box -->
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    @if ($case_closed_bersalah_medium > 1 || !empty($case_closed_bersalah_medium))
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
                                <a href="{{ route('opforum.closedcasesmediumguilt') }}" class="small-box-footer">Info
                                    Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-2 col-6">
                            <!-- small box -->
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    @if ($case_closed_bersalah_high > 1 || !empty($case_closed_bersalah_high))
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
                                <a href="{{ route('opforum.closedcaseshighguilt') }}" class="small-box-footer">Info
                                    Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                @include('pages.formauditor.opabsensi')
            </div>
            <!-- /.card -->
        </div>
        {{-- </div> --}}
    @endsection
