@extends('layouts.app')
@section('title','HS Forum (OP)')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item"><a href="{{ route('opforum.indexop') }}">HS Forum (OP)</a></li>
        <li class="breadcrumb-item active">Forum - Comments</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    @php
        $accessprivileges = \App\Models\Privilegeaccess::get();
    @endphp
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Form Comments</h3>
        </div>
        {!! Form::open(['route' => 'hsforum.update','enctype' => 'multipart/form-data']) !!}
        <div class="card-body">
            <div class="form-group">
                <input type="hidden" class="form-control" name="slug" value="{{ $dataforumaudit->slug }}">
                <input type="hidden" class="form-control" name="forum_id" value="{{ $dataforumaudit->id }}">
            </div>
            <div class="row">
                <div class="col-lg-2 col-6">
                    <div class="form-group">
                        <label for="case_id">Case ID<span>:</span></label>
                        <input class="form-control" name="case_id" id="case_id" value="{{ $dataforumaudit->case_id }}" readonly>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="form-group">
                        <label for="created_for">ID Admin<span>:</span></label>
                        <input class="form-control" name="created_for" id="created_for" value="{{ $dataforumaudit->created_for }}" readonly>
                    </div>
                </div>
                <div class="col-lg-3 col-12">
                    <div class="form-group">
                        <label for="created_for_name">Nama Staff<span>:</span></label>
                        <input type="text" class="form-control" name="created_for_name" id="created_for_name" value="{{ $dataforumaudit->created_for_name }}" readonly>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="form-group">
                        <label for="site_situs">Refer Situs<span>:</span></label>
                        <input type="text" class="form-control" name="site_situs" id="site_situs" value="{{ $dataforumaudit->site_situs }}" readonly>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="form-group">
                        <label for="created_by">Audit Oleh<span>:</span></label>
                        <input type="text" class="form-control" name="created_by" id="created_by" value="{{ $dataforumaudit->created_by }}" readonly>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="card-body deskripsi">
                    <div class="row">
                        <div class="col-lg-9 col-12">
                            <p><strong>Topik<span>:</span></strong></p>
                            {!!  $dataforumaudit->topik_title !!}
                            <hr>
                            <p><strong>Deskripsi Topik<span>:</span></strong></p>
                            {!! $dataforumaudit->topik_deskripsi !!}
                        </div>
                        <div class="col-lg-3 col-12">
                            <center><strong><span>Klik Gambar untuk perbesar</span></strong></center>
                            <div class="card-image">
                                <center>
                                    <a href="#" data-toggle="modal" data-target="#imagemodal">
                                         @if(!empty($imgUrl))
                                            <img src="{{ $imgUrl }}" alt="Lampiran" style="width: 100%; max-height: 150px; border-radius: 5px 5px 0px 0px;">
                                        @else
                                            <span class="text-muted small">Tidak ada gambar</span>
                                        @endif   
                                    </a>
                                </center>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="card-body komentar">
                    @if(count($dataforumauditpost)>0)
                        @foreach ($dataforumauditpost as $forumauditpost)
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
                <div class="card-body comment">
                    <div class="form-group">
                        <label for="summernote">Komentar Topik <span>:</span></label>
                        <textarea class="form-control" placeholder="Deskripsi Lengkap Topik" name="deskripsi_post" id="summernote"></textarea>
                    </div>
                </div>
            </div>

            {{-- @if(Auth::user()->posisi_kerja == 3 &&  Auth::user()->id_jabatan == 14) --}}
            @foreach($accessprivileges as $index => $ac)
                @if ($ac->id_admin == Auth::user()->id_admin && $ac->menu_id == 'HSF008')
                <div class="row">
                    <div class="card-body status">
                        <span><strong>Hanya boleh diupdate oleh Tim Audit*</strong></span>
                    <!-- status kesalahan 1=tidak bersalah, 2=bersalah -->
                        <div class="row">
                            <div class="col-lg-3 col-12">
                                <div class="form-group">
                                    <label for="status_kesalahan">Status Kesalahan<span>:</span></label>
                                        <select name="status_kesalahan" id="status_kesalahan" class="form-control" placeholder="Dipilih Status Kesalahan" required>
                                            <option value="0" {{ ($dataforumaudit->status_kesalahan == 0) ? 'selected' : '' }}>Belum Ditentukan</option>
                                            <option value="1" {{ ($dataforumaudit->status_kesalahan == 1) ? 'selected' : '' }}>Tidak Bersalah</option>
                                            <option value="2" {{ ($dataforumaudit->status_kesalahan == 2) ? 'selected' : '' }}>Bersalah (Low)</option>
                                            <option value="3" {{ ($dataforumaudit->status_kesalahan == 3) ? 'selected' : '' }}>Bersalah (Medium)</option>
                                            <option value="4" {{ ($dataforumaudit->status_kesalahan == 4) ? 'selected' : '' }}>Bersalah (High)</option>
                                        </select>
                                </div>
                            </div>
                            <!-- status case 2=on progress, 3=close -->
                            <div class="col-lg-3 col-12">
                                <div class="form-group">
                                    <label for="status_case">Final Status Topik<span>:</span></label>
                                        <select name="status_case" id="status_case" class="form-control" placeholder="Dipilih Status Topik" required>
                                            <option value="2" {{ ($dataforumaudit->status_case == 2) ? 'selected' : '' }}>On Progress</option>
                                            <option value="3" {{ ($dataforumaudit->status_case == 3) ? 'selected' : '' }}>Pending Topik</option>
                                            <option value="4" {{ ($dataforumaudit->status_case == 4) ? 'selected' : '' }}>Topik Close</option>
                                        </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-12">
                                <div class="form-group">
                                    <label for="periode">Periode:</label>
                                    <select name="periode" id="periode" class="form-control">
                                        @foreach ($periodes as $periode)
                                            @php
                                                $selected = (substr($dataforumaudit->periode, 0, 4) == $periode->tahun && substr($dataforumaudit->periode, -1) == $periode->periode) ? 'selected' : '';
                                            @endphp
                                            <option value="{{ $periode->tahun . $periode->periode }}" {{ $selected }}>
                                                {{ $periode->bulan_dari . ' - ' . $periode->bulan_ke }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
        <!-- /.card-body -->
        {!! Form::close() !!}
    </div>
    <!-- /.card -->
    <div class="modal fade" id="imagemodal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 1500px!important;">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    @if(!empty($imgUrl))
                        <img src="{{ $imgUrl }}" style="width: 100%;">
                    @endif  
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
<script>
    $(document).ready(function() {
        $('.dropify').dropify();
        $('#summernote').summernote({
            height: 300
        });
    })
</script>
@endpush
