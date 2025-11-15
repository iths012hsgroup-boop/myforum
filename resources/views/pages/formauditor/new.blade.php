@extends('layouts.app')
@section('title','HS Forum (Auditor)')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
        <li class="breadcrumb-item active">Form Tambah Forum</li>
    </ol>
@endsection
@section('content')
    <!-- Default box -->
    <div class="card table-responsive">
        <div class="card-header">
            <h3 class="card-title">Hello, <strong>{!! Auth::user()->id_admin; !!} / {!! Auth::user()->nama_staff; !!}</strong></h3>
        </div>

        {!! Form::open(['route' => 'hsforum.save','enctype' => 'multipart/form-data']) !!}
        <div class="card-body">
            <div class="row">
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-light">
                        <div class="inner">
                            @if($case_open > 1 || !empty($case_open))
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
                        <a href="{{ route('auditorforum.auditoropencases') }}" class="small-box-footer">Info Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-fuchsia">
                        <div class="inner">
                            @if($case_progress > 1 || !empty($case_progress))
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
                        <a href="{{ route('auditorforum.auditoronprogresscases') }}" class="small-box-footer">Info Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-indigo">
                        <div class="inner">
                            @if($case_progress_aman > 1 || !empty($case_progress_aman))
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
                        <a href="{{ route('auditorforum.auditoronprogressnoguiltcases') }}" class="small-box-footer">Info Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-orange">
                        <div class="inner">
                            @if($case_progress_bersalah > 1 || !empty($case_progress_bersalah))
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
                        <a href="{{ route('auditorforum.auditoronprogressguiltcases') }}" class="small-box-footer">Info Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-gray">
                        <div class="inner">
                            @if($case_pending > 1 || !empty($case_pending))
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
                        <a href="{{ route('auditorforum.auditoronpendingcases') }}" class="small-box-footer">Info Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
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
                        <a href="{{ route('auditorforum.auditorclosedcasesnoguilt') }}" class="small-box-footer">Info Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-2 col-6">
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
                        <a href="{{ route('auditorforum.auditorclosedcaseslowguilt') }}" class="small-box-footer">Info Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
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
                        <a href="{{ route('auditorforum.auditorclosedcasesmediumguilt') }}" class="small-box-footer">Info Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
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
                        <a href="{{ route('auditorforum.auditorclosedcaseshighguilt') }}" class="small-box-footer">Info Lebih... <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>
            <hr>

            <div class="form-group">
                <input type="hidden" class="form-control" name="case_id" id="case_id" required>
            </div>

            <div class="row">
                <div class="col-lg-4 col-6">
                    <div class="form-group">
                        <label for="created_for">Dibuat Untuk (ID Admin)<span>*</span></label>
                        <input class="form-control" list="datalistOptions" id="created_for" name="created_for" placeholder="Diketik dan Pilih Nama ID Admin" required>
                        <datalist id="datalistOptions">
                            @foreach ($datastaffs as $id => $datastaff)
                                <option value="{{ $id }}">{{ $datastaff }}</option>
                            @endforeach
                        </datalist>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="form-group">
                        <label for="created_for_name">Dibuat Untuk (Nama Staff)</label>
                        <input type="text" class="form-control" placeholder="Nama Staff" name="created_for_name" id="created_for_name" readonly>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="form-group">
                        <label for="site_situs">Refer Situs</label>
                        <select name="site_situs" id="site_situs" class="form-control" required>
                            <option value="">-- Silakan Pilih Situs --</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- TOPIK: dropdown dari tabel topik --}}
            <div class="form-group">
                <label for="topik_title">Topik <span>*</span></label>
                <select name="topik_title" id="topik_title" class="form-control select2" required>
                    <option value="">-- Silakan Pilih Topik --</option>
                    @foreach($topikList as $title)
                        <option value="{{ $title }}" {{ old('topik_title') === $title ? 'selected' : '' }}>
                            {{ $title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="link_gambar">Upload Gambar <span>*</span></label>
                <small class="form-text text-muted">
                    Kamu juga bisa paste gambar langsung dari clipboard (Ctrl+V) setelah copy screenshot.
                </small>
                <input
                    type="file"
                    class="dropify"
                    name="link_gambar"
                    id="link_gambar"
                    accept=".jpg,.jpeg,.png,.gif"
                    required
                />
            </div>

            <div class="form-group">
                <label for="summernote">Deskripsi Topik <span>*</span></label>
                <textarea class="form-control" placeholder="Deskripsi Lengkap Topik" name="topik_deskripsi" id="summernote" required>{{ old('topik_deskripsi') }}</textarea>
            </div>

            <div class="row">
                <div class="col-lg-6 col-6">
                    <div class="form-group">
                        <label for="created_by">Di buat oleh (ID Admin)<span>*</span></label>
                        <input type="text" class="form-control" placeholder="ID Admin" name="created_by" id="created_by" value="{!! Auth::user()->id_admin; !!}" readonly>
                    </div>
                </div>
                <div class="col-lg-6 col-6">
                    <div class="form-group">
                        <label for="created_by_name">Di buat oleh (Nama Staff) <span>*</span></label>
                        <input type="text" class="form-control" placeholder="Nama Staff" name="created_by_name" id="created_by_name" value="{!! Auth::user()->nama_staff; !!}" readonly>
                    </div>
                </div>
            </div>

            {{-- Periode: default selected dikirim dari controller --}}
            <div class="form-group">
                <label for="periode">Periode <span>*</span></label>
                <select name="periode" id="periode" class="form-control" required>
                    <option value=""> -- Silakan Pilih Periode --</option>
                    @foreach ($periodes as $periode)
                        @php $val = $periode->tahun . $periode->periode; @endphp
                        <option value="{{ $val }}" {{ ($selectedPeriode === $val) ? 'selected' : '' }}>
                            {{ $periode->bulan_dari . ' - ' . $periode->bulan_ke }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status Case (nilai angka biar aman di DB) --}}
            <div class="form-group">
                <label for="status_case">Status Topik <span>*</span></label>
                @php $curStatus = old('status_case', 1); @endphp
                <select name="status_case" id="status_case" class="form-control" required>
                    <option value="1" {{ $curStatus == 1 ? 'selected' : '' }}>Open</option>
                    <option value="2" {{ $curStatus == 2 ? 'selected' : '' }}>On Progress</option>
                    <option value="3" {{ $curStatus == 3 ? 'selected' : '' }}>Pending</option>
                    <option value="4" {{ $curStatus == 4 ? 'selected' : '' }}>Closed</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Tambah</button>
        </div>
        {!! Form::close() !!}
    </div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.dropify').dropify();
        $('#summernote').summernote({ height: 300 });

        // Jadikan dropdown Topik searchable
        if ($.fn.select2) {
            $('#topik_title').select2({
                theme: 'bootstrap4',
                placeholder: '-- Silakan Pilih Topik --',
                allowClear: true,
                width: '100%'
            });
        }

        // Generate case_id sederhana (YYYYMMDDHHMMSS)
        var d = new Date();
        var pad = n => (n < 10 ? '0' + n : '' + n);
        var caseid = d.getFullYear()
                    + pad(d.getMonth()+1)
                    + pad(d.getDate())
                    + pad(d.getHours())
                    + pad(d.getMinutes())
                    + pad(d.getSeconds());
        document.getElementById("case_id").value = caseid;
    });

    $('#created_for').change(function(){
        var id = $(this).val();
        var url = '{{ route('other.getnamastaff', ':id') }}'.replace(':id', id);
        var urlsite = '{{ route('other.getnamasitus', ':id') }}'.replace(':id', id);

        $.getJSON(url, function(response){
            if (response !== null && response.length > 0) {
                $('#created_for_name').val(response[0].nama_staff);
            }
        });

        $.getJSON(urlsite, function(situs){
            if (situs !== null) {
                var namaSitusArray = Array.isArray(situs) ? situs : Object.values(situs);
                $('#site_situs').empty().append('<option value="">-- Silakan Pilih Situs --</option>');
                $.each(namaSitusArray, function(_, value) {
                    $('#site_situs').append('<option value="'+ value +'">'+ value +'</option>');
                });
                if (namaSitusArray.length === 1) $('#site_situs').val(namaSitusArray[0]);
            }
        });
    });


    $(document).ready(function() {
    $('.dropify').dropify();
    $('#summernote').summernote({ height: 300 });

    // ====== PASTE IMAGE KE INPUT #link_gambar ======
    $(document).on('paste', function (e) {
        // kalau fokus di Summernote, biarin Summernote yang handle paste
        const active = document.activeElement;
        if ($(active).closest('.note-editor').length) {
            return;
        }

        const clipboardData = e.originalEvent.clipboardData || e.clipboardData;
        if (!clipboardData || !clipboardData.items) return;

        let file = null;
        for (let i = 0; i < clipboardData.items.length; i++) {
            const item = clipboardData.items[i];
            if (item.kind === 'file' && item.type.indexOf('image') === 0) {
                file = item.getAsFile();
                break;
            }
        }

        if (!file) return; // tidak ada image di clipboard

        // buat DataTransfer untuk set input.files
        const dt = new DataTransfer();
        dt.items.add(file);

        const input = document.getElementById('link_gambar');
        if (!input) return;

        input.files = dt.files;

        // trigger change supaya Dropify update preview
        $(input).trigger('change');

        // optional: kasih notifikasi kecil
        console.log('Gambar dari clipboard dimasukkan ke input file.');
    });

    // ====== kode kamu yg lama di bawah ini tetap ======
    // Jadikan dropdown Topik searchable
    if ($.fn.select2) {
        $('#topik_title').select2({
            theme: 'bootstrap4',
            placeholder: '-- Silakan Pilih Topik --',
            allowClear: true,
            width: '100%'
        });
    }

    // Generate case_id sederhana (YYYYMMDDHHMMSS)
    var d = new Date();
    var pad = n => (n < 10 ? '0' + n : '' + n);
    var caseid = d.getFullYear()
                + pad(d.getMonth()+1)
                + pad(d.getDate())
                + pad(d.getHours())
                + pad(d.getMinutes())
                + pad(d.getSeconds());
    document.getElementById("case_id").value = caseid;
});
</script>
@endpush
