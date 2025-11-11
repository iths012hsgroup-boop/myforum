@extends('layouts.app')
@section('title','HS Forum')

@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('auditorforum.new') }}">HS Forum (Auditor)</a></li>
        <li class="breadcrumb-item active">Forum - Details Cases</li>
    </ol>
@endsection

@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Data On Details Closed Cases</h3>
        </div>

        {!! Form::open(['route' => 'hsforum.update','enctype' => 'multipart/form-data']) !!}
        <div class="card-body">
            {{-- ===== Hidden context ===== --}}
            <div class="form-group">
                <input type="hidden" class="form-control" name="slug" value="{{ $datadetailforumaudit->slug }}">
                <input type="hidden" class="form-control" name="forum_id" value="{{ $datadetailforumaudit->id }}">
                <input type="hidden" name="action" value="comment">
            </div>

            {{-- ===== Header detail ===== --}}
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

            {{-- ===== Topik & deskripsi + gambar ===== --}}
            <div class="row">
                <div class="card-body deskripsi">
                    <div class="row">
                        <div class="col-lg-9 col-12">
                            <p><strong>Topik<span>:</span></strong></p>
                            {!! $datadetailforumaudit->topik_title !!}
                            <hr>
                            <p><strong>Deskripsi Topik<span>:</span></strong></p>
                            {!! $datadetailforumaudit->topik_deskripsi !!}
                        </div>
                        <div class="col-lg-3 col-12">
                            <center><strong><span>Klik Gambar untuk perbesar</span></strong></center>
                            <div class="card-image">
                                <center>
                                    <a href="#" data-toggle="modal" data-target="#imagemodal">
                                        @if(!empty($imageUrl))
                                            <img src="{{ $imageUrl }}" alt="Lampiran" style="width: 100%; max-height: 150px; border-radius: 5px 5px 0 0;">
                                        @else
                                            <div class="text-muted small">Tidak ada gambar</div>
                                        @endif
                                    </a>
                                </center>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Daftar komentar ===== --}}
            <div class="row mt-3">
                <div class="card-body komentar">
                    @if(($comments->count() ?? 0) > 0)
                        @foreach ($comments as $c)
                            <div class="col-md-12">
                                <p>
                                    Komentar Oleh: <strong>{{ $c['updated_by'] }}</strong><br>
                                    Pada tanggal: <strong>{{ $c['created_at_hms'] }}</strong>
                                </p>
                                {!! $c['deskripsi_post'] !!}
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

            {{-- ===== FORM KOMENTAR (tampil jika boleh) ===== --}}
            @if ($canComment)
            <div class="row mt-3" id="comment-form">
                <div class="card-body w-100">
                    <label for="deskripsi_post"><strong>Komentar Topik :</strong></label>
                    <textarea
                        id="deskripsi_post"
                        name="deskripsi_post"
                        class="form-control"
                        rows="7"
                        placeholder="Tulis komentar di sini…"
                        required
                    ></textarea>

                    <div class="mt-3 d-flex">
                        <button type="submit" class="btn btn-primary">Simpan Komentar</button>
                        <a href="{{ url()->previous() }}" class="btn btn-light ml-2">Batal</a>
                    </div>
                </div>
            </div>
            @else
            <div class="row mt-3">
                <div class="card-body w-100">
                    <div class="alert alert-light border mb-0">
                        Komentar dinonaktifkan karena topik sudah <strong>Close</strong>.
                    </div>
                </div>
            </div>
            @endif

            {{-- ===== Status ringkas (sudah dinormalisasi di Controller) ===== --}}
            <div class="row">
                <div class="card-body status">
                    <div class="row">
                        <div class="col-lg-3 col-12">
                            <div class="form-group">
                                <label for="status_kesalahan">Status Kesalahan<span>:</span></label>
                                <input type="text" class="form-control" id="status_kesalahan" value="{{ $statusKesalahanText }}" readonly>
                            </div>
                        </div>
                        <div class="col-lg-3 col-12">
                            <div class="form-group">
                                <label for="status_case">Final Status Topik<span>:</span></label>
                                <input type="text" class="form-control" id="status_case" value="{{ $statusCaseText }}" readonly>
                            </div>
                        </div>
                        <div class="col-lg-3 col-12">
                            <div class="form-group">
                                <label for="periode">Periode Topik<span>:</span></label>
                                <input type="text" class="form-control" id="periode" value="{{ $datadetailforumaudit->periode }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end .card-body --}}
        {!! Form::close() !!}
    </div>
    <!-- /.card -->

    {{-- Modal preview gambar --}}
    <div class="modal fade" id="imagemodal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 1920px!important;">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    @if(!empty($imageUrl))
                        <img src="{{ $imageUrl }}" style="width: 100%;">
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Inisialisasi editor jika ada plugin (mis. Summernote).
    $(function () {
        if ($.fn.summernote && document.getElementById('deskripsi_post')) {
            $('#deskripsi_post').summernote({ height: 220 });
        }
    });

    // Auto-scroll ke form komentar saat datang dari dashboard (?comment=1 atau anchor)
    $(function () {
        const params = new URLSearchParams(window.location.search);
        if ((params.get('comment') === '1' || window.location.hash === '#comment-form')
            && document.getElementById('comment-form')) {
            setTimeout(function () {
                const target = document.getElementById('comment-form');
                if (target) target.scrollIntoView({behavior: 'smooth', block: 'start'});
                const ta = document.querySelector('#deskripsi_post');
                if (ta) ta.focus();
            }, 120);
        }
    });
</script>
@endpush
