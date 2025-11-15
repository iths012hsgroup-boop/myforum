@extends('layouts.app')

@section('title','HS Forum')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('auditorforum.new') }}">HS Forum (Auditor)</a></li>
        <li class="breadcrumb-item active">Forum - Comments</li>
    </ol>
@endsection

@section('content')
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Form Comments</h3>
        </div>

        {!! Form::open(['route' => 'hsforum.update','enctype' => 'multipart/form-data']) !!}
        <div class="card-body">
            {{-- Hidden IDs --}}
            <input type="hidden" name="slug" value="{{ $datadetailforumaudit->slug }}">
            <input type="hidden" name="forum_id" value="{{ $datadetailforumaudit->id }}">

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

            {{-- Deskripsi & Gambar --}}
            <div class="row">
                <div class="col-12">
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
                                <div class="card-image mt-2">
                                    <center>
                                        <a href="#" data-toggle="modal" data-target="#imagemodal">
                                            @if(!empty($imageUrl))
                                                <img src="{{ $imageUrl }}" alt="Gambar Topik" style="width: 100%; max-height: 150px; border-radius: 5px 5px 0 0;">
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
            </div>

            {{-- Daftar Komentar --}}
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card-body komentar">
                        @if($datadetailforumauditpost->count() > 0)
                            @foreach ($datadetailforumauditpost as $forumauditpost)
                                <div class="mb-3">
                                    <p>
                                        Komentar Oleh: <strong>{{ $forumauditpost->updated_by }}</strong><br>
                                        Pada tanggal: <strong>{{ optional($forumauditpost->created_at)->format('Y-m-d H:i:s') }}</strong>
                                    </p>
                                    {!! $forumauditpost->deskripsi_post !!}
                                    <hr>
                                </div>
                            @endforeach
                        @else
                            <p><strong>Belum ada Komentar!!!</strong></p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tambah Komentar --}}
            <div class="row">
                <div class="col-12">
                    <div class="card-body comment">
                        <div class="form-group">
                            <label for="summernote">Komentar Topik <span>:</span></label>
                            <textarea class="form-control" placeholder="Deskripsi Lengkap Topik" name="deskripsi_post" id="summernote" required>{{ old('deskripsi_post') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status hanya untuk yang punya akses --}}
            @if (!empty($canUpdateStatus))
                @php
                    $curErr  = old('status_kesalahan', $datadetailforumaudit->status_kesalahan ?? 0);
                    $curCase = old('status_case', $datadetailforumaudit->status_case ?? 2);
                @endphp
                <div class="row">
                    <div class="col-12">
                        <div class="card-body status">
                            <span><strong>Hanya boleh diupdate oleh Tim Audit*</strong></span>
                            <div class="row">
                                {{-- Status Kesalahan: 0..4 --}}
                                <div class="col-lg-3 col-12">
                                    <div class="form-group">
                                        <label for="status_kesalahan">Status Kesalahan<span>:</span></label>
                                        <select name="status_kesalahan" id="status_kesalahan" class="form-control" required>
                                            <option value="0" {{ $curErr == 0 ? 'selected' : '' }}>Belum Ditentukan</option>
                                            <option value="1" {{ $curErr == 1 ? 'selected' : '' }}>Tidak Bersalah</option>
                                            <option value="2" {{ $curErr == 2 ? 'selected' : '' }}>Bersalah (Low)</option>
                                            <option value="3" {{ $curErr == 3 ? 'selected' : '' }}>Bersalah (Medium)</option>
                                            <option value="4" {{ $curErr == 4 ? 'selected' : '' }}>Bersalah (High)</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Status Case: 2..4 --}}
                                <div class="col-lg-3 col-12">
                                    <div class="form-group">
                                        <label for="status_case">Final Status Topik<span>:</span></label>
                                        <select name="status_case" id="status_case" class="form-control" required>
                                            <option value="2" {{ $curCase == 2 ? 'selected' : '' }}>On Progress</option>
                                            <option value="3" {{ $curCase == 3 ? 'selected' : '' }}>Pending Topik</option>
                                            <option value="4" {{ $curCase == 4 ? 'selected' : '' }}>Topik Close</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Periode --}}
                                <div class="col-lg-3 col-12">
                                    <div class="form-group">
                                        <label for="periode">Periode:</label>
                                        <select name="periode" id="periode" class="form-control" required>
                                            @foreach ($periodeOptions as $opt)
                                                <option value="{{ $opt->value }}" {{ $opt->selected ? 'selected' : '' }}>
                                                    {{ $opt->label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
        {!! Form::close() !!}
    </div>

    {{-- Modal Preview Gambar --}}
    <div class="modal fade" id="imagemodal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 1500px!important;">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
                    </button>
                    @if(!empty($imageUrl ?? ''))
                        <img src="{{ $imageUrl }}" style="width: 100%;">
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.dropify').dropify?.();
        if ($.fn.summernote) {
            $('#summernote').summernote({ height: 300 });
        }
    });
</script>
@endpush
