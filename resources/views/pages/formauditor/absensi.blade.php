@push('styles')
<style>
    .legend-wrapper {
        position: absolute;
        top: 2px;       /* sedikit turun */
        right: -303px;   /* geser kanan, silakan sesuaikan */
        width: 220px;      /* lebih lebar */
        padding: 20px;     /* padding lebih besar */
        background: #fff;
        border: 1px solid #dee2e6;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        gap: 11px;
    }

    .legend-box {
        width: 75px;       /* lebih panjang */
        height: 32px;      /* lebih tinggi */
        font-size: 16px;   /* huruf lebih besar */
    }

    .legend-text {
        margin-left: 8px;
        font-weight: bold;
        font-size: 14px;   /* huruf lebih besar */
        color: #6c757d;
        white-space: nowrap;
    }

    /* CARD ABSENSI LEBIH BESAR */
    .absensi-card {
        min-width: 1240px;
        min-height: 290px;
    }

    /* PERBESAR TABEL DI DALAM CARD */
    .absensi-card table {
        font-size: 17px;     /* sebelumnya 15px, silakan sesuaikan */
    }

    .absensi-card th,
    .absensi-card td {
        padding-top: 6px;
        padding-bottom: 6px;
    }

    /* tinggi baris kedua (ikon H / T / S / ...) */
    .absensi-card tbody tr:last-child td > div {
        height: 55px;        /* sebelumnya 45px */
    }

    .legend-telat       { background-color: #ffc107; } /* T */
    .legend-sakit       { background-color: #007bff; } /* S */
    .legend-izin        { background-color: #17a2b8; } /* I */
    .legend-tanpa-kabar { background-color: #dc3545; } /* TK */
    .legend-cuti        { background-color: #27ae60; } /* C */
    .legend-hadir       { background-color: #ffffff; } /* H */
</style>
@endpush

<div class="container-fluid mt-4">
    <div class="row">
        {{-- KOLOM KIRI: CARD + TABEL ABSENSI --}}
        <div class="col-12">
            <div class="position-relative d-inline-block">
                <div class="card shadow-lg border-success absensi-card">
                <div class="card-header text-center bg-success text-white">
                    <h2 class="fw-bold mb-0"><strong>ABSENSI</strong></h2>
                    <h3><strong>{{ $tahunSekarang }}</strong></h3>
                </div>

                <div class="card-body p-2">
                    <div class="d-flex justify-content-end mb-2">
                        <select name="bulan" id="bulan" class="form-control form-control-sm"
                                onchange="location.search='?bulan='+this.value">
                            @foreach ($dropdownMonths as $m)
                                <option value="{{ $m['key'] }}"
                                    {{ $m['key'] === ($selectedBulan ?? '') ? 'selected' : '' }}>
                                    {{ $m['nama'] }} {{ $m['tahun'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm text-center align-middle mb-0"
                            style="font-size: 15px; width: 100%;">

                            <thead class="table-success">
                                <tr>
                                    <th style="width: 10%;">BULAN</th>
                                    @for ($i = 1; $i <= $hariDalamBulanAktif; $i++)
                                        <th style="width: calc(85% / {{ $hariDalamBulanAktif }});">
                                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                        </th>
                                    @endfor
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($bulanList as $b)
                                    <tr>
                                        <td class="fw-bold bg-info text-dark text-center align-middle"
                                            rowspan="2"
                                            style="vertical-align: middle; text-transform: uppercase;">
                                            {{ $b['nama'] }}
                                        </td>

                                        @foreach ($activeDays as $day)
                                            <td class="bg-secondary text-white fw-bold border border-danger p-1 day-label">
                                                {{ $day['label'] }}
                                            </td>
                                        @endforeach
                                    </tr>

                                    <tr>
                                        @foreach ($activeDays as $day)
                                            <td class="{{ $day['color'] }} border border-danger p-0">
                                                <div class="d-flex justify-content-center align-items-center"
                                                     style="height: 45px;">
                                                    <span
                                                        class="fw-bold absensi-icon {{ $day['status'] === 'HADIR' ? '' : 'absensi-clickable' }}"
                                                        style="{{ $day['status'] === 'HADIR' ? '' : 'cursor:pointer;' }}"
                                                        data-date="{{ $day['tanggal'] }}"
                                                        data-status="{{ $day['status'] }}"
                                                        data-remarks="{{ $day['remarks'] }}">
                                                        {{ $day['icon'] }}
                                                    </span>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

                                {{-- LEGEND 2×3 DI KANAN --}}
<div class="legend-wrapper">
    <div class="d-flex align-items-center">
        <div class="legend-box legend-hadir border d-flex justify-content-center align-items-center">
            H
        </div>
        <span class="legend-text">Hadir</span>
    </div>

    <div class="d-flex align-items-center">
        <div class="legend-box legend-telat border d-flex justify-content-center align-items-center">
            T
        </div>
        <span class="legend-text">Telat</span>
    </div>

    <div class="d-flex align-items-center">
        <div class="legend-box legend-sakit text-white border d-flex justify-content-center align-items-center">
            S
        </div>
        <span class="legend-text">Sakit</span>
    </div>

    <div class="d-flex align-items-center">
        <div class="legend-box legend-izin text-white border d-flex justify-content-center align-items-center">
            I
        </div>
        <span class="legend-text">Izin</span>
    </div>

    <div class="d-flex align-items-center">
        <div class="legend-box legend-tanpa-kabar text-white border d-flex justify-content-center align-items-center">
            TK
        </div>
        <span class="legend-text">Tanpa Kabar</span>
    </div>

    <div class="d-flex align-items-center">
        <div class="legend-box legend-cuti text-white border d-flex justify-content-center align-items-center">
            C
        </div>
        <span class="legend-text">Cuti</span>
    </div>
</div>
                            </div> {{-- end position-relative --}}
                        </div>
                    </div>
                </div>

                {{-- Modal Detail Absensi --}}
                <div class="modal fade" id="absensiDetailModal" tabindex="-1" role="dialog"
                     aria-labelledby="absensiDetailModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="absensiDetailModalLabel">Attendance Detail</h5>
                                <button type="button" class="close text-white" data-dismiss="modal"
                                        aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Date:</strong> <span id="modalAbsensiDate"></span></p>
                                <p><strong>Status:</strong> <span id="modalAbsensiStatus"></span></p>
                                <p><strong>Remarks:</strong><br>
                                    <span id="modalAbsensiRemarks"></span>
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                        data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- end modal --}}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function makeLinksClickable(text) {
        const raw = (text || '').toString();

        const urlRegex = /(https?:\/\/[^\s]+|www\.[^\s]+)/g;
        return raw.replace(urlRegex, function (url) {
            const fullUrl = url.startsWith('http') ? url : 'https://' + url;
            return `<a href="${fullUrl}" target="_blank" rel="noopener noreferrer">${url}</a>`;
        });
    }

    $(document).on('click', '.absensi-clickable', function () {
        const date    = $(this).data('date');
        const status  = $(this).data('status');
        const remarks = $(this).data('remarks');

        $('#modalAbsensiDate').text(date);
        $('#modalAbsensiStatus').text(status);
        $('#modalAbsensiRemarks').html(makeLinksClickable(remarks));

        $('#absensiDetailModal').modal('show');
    });
</script>
@endpush
