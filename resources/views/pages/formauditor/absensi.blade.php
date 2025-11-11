@push('styles')
<style>
    .absensi-container {
        position: relative;    /* anchor untuk legend absolute */
    }

    .absensi-legend-outer {
        position: absolute;
        top: 1px;
        right: -128px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }

    .absensi-legend-item {
        display: flex;
        align-items: center;
        margin-bottom: 4px;
    }

    .absensi-legend-box {
        width: 40px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: bold;
        font-size: 14px;
    }

    .absensi-legend-text {
        margin-left: 6px;
        font-size: 14px;
        color: #555;
        white-space: nowrap;
        font-weight: bolder;
    }

    .legend-telat       { background-color: #ffc107; } /* T */
    .legend-sakit       { background-color: #007bff; } /* S */
    .legend-izin        { background-color: #00b894; } /* I */
    .legend-tanpa-kabar { background-color: #e74c3c; } /* TK */
    .legend-cuti        { background-color: #27ae60; } /* C */
</style>
@endpush

<div class="container mt-4">
    <div class="absensi-row">
        <div class="flex-grow-1">
            <div class="absensi-container"><!-- 🔹 sekarang legend nempel ke card ini -->
                <div class="card shadow-lg border-success">
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

                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm text-center align-middle mb-0"
                                       style="font-size: 15px; table-layout: fixed; width: 100%;">

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
                                            {{-- Baris nama bulan + hari --}}
                                            <tr>
                                                <td class="fw-bold bg-info text-dark text-center align-middle"
                                                    rowspan="2"
                                                    style="vertical-align: middle; text-transform: uppercase;">
                                                    {{ $b['nama'] }}
                                                </td>

                                                {{-- Baris hari (2 huruf EN) --}}
                                                @foreach ($activeDays as $day)
                                                    <td class="bg-secondary text-white fw-bold border border-danger p-1">
                                                        {{ $day['label'] }}
                                                    </td>
                                                @endforeach
                                            </tr>

                                            {{-- Baris status absensi --}}
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

                            {{-- LEGEND DI KANAN --}}
                            <div class="absensi-legend-outer">
                                <div class="absensi-legend-item">
                                    <div class="absensi-legend-box legend-telat">T</div>
                                    <span class="absensi-legend-text">Telat</span>
                                </div>
                                <div class="absensi-legend-item">
                                    <div class="absensi-legend-box legend-sakit">S</div>
                                    <span class="absensi-legend-text">Sakit</span>
                                </div>
                                <div class="absensi-legend-item">
                                    <div class="absensi-legend-box legend-izin">I</div>
                                    <span class="absensi-legend-text">Izin</span>
                                </div>
                                <div class="absensi-legend-item">
                                    <div class="absensi-legend-box legend-tanpa-kabar">TK</div>
                                    <span class="absensi-legend-text">Tanpa Kabar</span>
                                </div>
                                <div class="absensi-legend-item">
                                    <div class="absensi-legend-box legend-cuti">C</div>
                                    <span class="absensi-legend-text">Cuti</span>
                                </div>
                            </div>
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
