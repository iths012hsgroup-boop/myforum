@extends('layouts.app')
@section('title','HS Forum')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('hsforum.index') }}">HS Forum</a></li>
        <li class="breadcrumb-item active">Forum - Details Cases</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Data On Details Closed Cases</h3>
        </div>
        {!! Form::open(['route' => 'hsforum.update','enctype' => 'multipart/form-data']) !!}
        <div class="card-body">
            <div class="form-group">
                <input type="hidden" class="form-control" name="slug" value="{{ $datadetailforumaudit->slug }}">
                <input type="hidden" class="form-control" name="forum_id" value="{{ $datadetailforumaudit->id }}">
            </div>
            <div class="row">
                <div class="col-lg-2 col-6">
                    <div class="form-group">
                        <label for="case_id">Case ID<span>:</span></label>
                        <input class="form-control" name="case_id" id="case_id" value="{{ $datadetailforumaudit->case_id }}" readonly>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="form-group">
                        <label for="created_for">ID Admin<span>:</span></label>
                        <input class="form-control" name="created_for" id="created_for" value="{{ $datadetailforumaudit->created_for }}" readonly>
                    </div>
                </div>
                <div class="col-lg-3 col-12">
                    <div class="form-group">
                        <label for="created_for_name">Nama Staff<span>:</span></label>
                        <input type="text" class="form-control" name="created_for_name" id="created_for_name" value="{{ $datadetailforumaudit->created_for_name }}" readonly>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="form-group">
                        <label for="site_situs">Refer Situs<span>:</span></label>
                        <input type="text" class="form-control" name="site_situs" id="site_situs" value="{{ $datadetailforumaudit->site_situs }}" readonly>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="form-group">
                        <label for="created_by">Audit Oleh<span>:</span></label>
                        <input type="text" class="form-control" name="created_by" id="created_by" value="{{ $datadetailforumaudit->created_by }}" readonly>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="card-body deskripsi">
                    <div class="row">
                        <div class="col-lg-9 col-12">
                            <p><strong>Topik<span>:</span></strong></p>
                            {!!  $datadetailforumaudit->topik_title !!}
                            <hr>
                            <p><strong>Deskripsi Topik<span>:</span></strong></p>
                            {!! $datadetailforumaudit->topik_deskripsi !!}
                        </div>
                        <div class="col-lg-3 col-12">
                            <center><strong><span>Klik Gambar untuk perbesar</span></strong></center>
                            <div class="card-image">
                                <center>
                                    <a href="#" data-toggle="modal" data-target="#imagemodal">
                                        <img src="{{ $datadetailforumaudit->link_gambar ? '/storage/'.$datadetailforumaudit->link_gambar : '' }}" alt="" style="width: 100%; max-height: 150px; border-radius: 5px 5px 0px 0px;">
                                    </a>
                                </center>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="card-body komentar">
                    @if(count($datadetailforumauditpost)>0)
                        @foreach ($datadetailforumauditpost as $forumauditpost)
                        <div class="col-md-12">
                            <p>Komentar Oleh: <strong>{{ $forumauditpost->updated_by  }}</strong> <br> Pada tanggal: <strong>{{ $forumauditpost->created_at }}</strong></p>
                            {!! $forumauditpost->deskripsi_post !!}
                            <hr>
                        </div>
                        @endforeach
                    @else
                        <div class="col-md-12">
                            <p><strong>Belum ada Komentar!!!</strong></p>
                        </div>
                    @endif
                </div>
            </div>
            <div class="row">
                <div class="card-body status">
                    <!-- status kesalahan 1=tidak bersalah, 2=bersalah (low), 3=bersalah (medium), 4=bersalah (High) -->
                    <div class="row">
                        <div class="col-lg-3 col-12">
                            <div class="form-group">
                                <label for="status_kesalahan">Status Kesalahan<span>:</span></label>
                                <input type="text" class="form-control" name="status_kesalahan" id="status_kesalahan" value="{{ $datadetailforumaudit->status_kesalahan}}" readonly>
                            </div>
                        </div>
                        <!-- status case 2=on progress, 3=close -->
                        <div class="col-lg-3 col-12">
                            <div class="form-group">
                                <label for="status_case">Final Status Topik<span>:</span></label>
                                <input type="text" class="form-control" name="status_case" id="status_case" value="{{ $datadetailforumaudit->status_case}}" readonly>
                            </div>
                        </div>
                        <div class="col-lg-3 col-12">
                            <div class="form-group">
                                <label for="status_case">Periode Topik<span>:</span></label>
                                <input type="text" class="form-control" name="periode" id="periode" value="{{ $datadetailforumaudit->periode }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card-body -->
        {!! Form::close() !!}
    </div>
    <!-- /.card -->
    <div class="modal fade" id="imagemodal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 1920px!important;">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    <img src="{{ $datadetailforumaudit->link_gambar ? '/storage/'.$datadetailforumaudit->link_gambar : '' }}" style="width: 100%;" >
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
<script>
    $(document).ready(function() {
        const status_kesalahan = document.getElementById("status_kesalahan");
        if (status_kesalahan.value == 1) {
            document.getElementById("status_kesalahan").value = "Tidak Bersalah";
        } else if (status_kesalahan.value == 2) {
            document.getElementById("status_kesalahan").value = "Bersalah (Low)";
        } else if (status_kesalahan.value == 3) {
            document.getElementById("status_kesalahan").value = "Bersalah (Medium)";
        } else if (status_kesalahan.value == 4) {
            document.getElementById("status_kesalahan").value = "Bersalah (High)";
        }
    });

    $(document).ready(function() {
        const status_case = document.getElementById("status_case");
        if (status_case.value == 1) {
            document.getElementById("status_case").value = "Open";
        } else if (status_case.value == 2) {
            document.getElementById("status_case").value = "On Progress";
        } else if (status_case.value == 3) {
            document.getElementById("status_case").value = "Pending Topik";
        } else if (status_case.value == 4) {
            document.getElementById("status_case").value = "Topik Close";
        }
    });
</script>
@endpush
