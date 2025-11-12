@push('styles')
<style>
    /* ===== STAFF CONTAINER & PILL ===== */
    #staffContainer {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px;
    }

    .staff-pill-wrapper {
        width: 170px;
        padding: 8px 10px;
        border: 1px solid rgb(255, 231, 231);
        border-radius: 10px;
        background-color: #127180;
        box-shadow: 0 2px 4px rgba(0, 0, 0, .06);
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        cursor: pointer;
        transition: box-shadow .15s, transform .15s;
    }
    .staff-pill-wrapper:hover {
        box-shadow: 0 2px 6px rgba(0, 0, 0, .12);
        transform: translateY(-1px);
    }

    .staff-pill-name {
        font-size: 13px;
        font-weight: 600;
        line-height: 1.2;
        max-width: 150px;
        white-space: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        -ms-overflow-style: none;
        scrollbar-width: none;
        text-align: center;
    }
    .staff-pill-name::-webkit-scrollbar {
        display: none;
    }

    .staff-name {
        font-weight: 600;
        font-size: 14px;
        color: #fff;
    }

    .staff-id {
        font-size: 12px;
        color: #e2e2e2;
    }

    /* ===== REMARKS COLUMN ===== */
    td.col-remarks {
        white-space: normal !important;
        word-wrap: break-word;
        word-break: break-word;
        max-width: 260px;
    }

    #detailAbsensiTable th:nth-child(1),
    #detailAbsensiTable td:nth-child(1) {
        width: 1%;
        text-align: center;
    }

    /* SELECT MULTI SITUS (Choices) */
    .choices__inner {
        max-height: 80px;
        overflow-y: auto;
    }

    /* CARD HEADER BIRU UNTUK PILIH SITUS & STAFF TOOLS */
    .card-header-turquoise {
        background: #17a2b8;
        border-radius: 10px;
    }

    .staff-tools-card {
        height: 60px;
        width: 1010px;
        position: relative;
        right: 100px;
    }
</style>
@endpush

<div class="tab-pane fade" id="cases" role="tabpanel" aria-labelledby="cases-tab">

    @if ($sites->isEmpty())
        <p class="text-center text-muted">Belum ada data situs.</p>
    @else

        {{-- SUMMARY KECIL OP ABSENSI --}}
        <div class="row justify-content-center mb-3">
            <div class="col-md-4 mb-2">
                <div class="card shadow-sm border-left-primary">
                    <div class="card-body py-2">
                        <div class="small text-muted">Total Situs</div>
                        <div class="h5 mb-0" id="card-total-sites">-</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card shadow-sm border-left-success">
                    <div class="card-body py-2">
                        <div class="small text-muted">Total Staff Aktif</div>
                        <div class="h5 mb-0" id="card-total-staff">-</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PILIH SITUS (SELECT) --}}
        <div class="row justify-content-center mb-3">
            <div class="col-lg-8 col-xl-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body py-2 card-header-turquoise">
                        <div class="d-flex align-items-center mb-2">
                            <button type="button" class="btn btn-sm btn-outline-info rounded-circle mr-2">
                                <i class="fa fa-diamond" style="color: white"></i>
                            </button>
                            <div>
                                <div class="font-weight-bold small mb-0" style="color: white">Pilih Situs</div>
                                <small class="text-muted d-block" style="color: black !important;">
                                    Pilih satu atau beberapa situs
                                </small>
                            </div>
                        </div>

                        <select id="siteSelect"
                                class="form-control form-control-sm"
                                multiple
                                data-placeholder="Pilih situs...">
                            @foreach ($sites as $situs)
                                <option value="{{ $situs->id }}">{{ $situs->nama_situs }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- EMPTY STATE --}}
        <div id="opEmptyState" class="row justify-content-center mt-4">
            <div class="col-lg-6 text-center text-muted">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h5 class="mb-1">Belum ada situs yang dipilih</h5>
                <p class="mb-2">
                    Pilih satu atau beberapa situs di atas untuk menampilkan daftar staff
                    dan menginput absensi.
                </p>
                <ul class="list-unstyled small mb-0">
                    <li>1. Pilih situs pada kotak <strong>"Pilih Situs"</strong></li>
                    <li>2. Daftar staff akan muncul di bawah</li>
                    <li>3. Klik nama staff untuk input absensi</li>
                </ul>
            </div>
        </div>

        {{-- SECTION STAFF --}}
        <div id="staffSection" style="display:none;">
            <div class="row justify-content-center mb-2">
                <div class="col-lg-8 col-xl-6">
                    <div class="card bg-light border-0 staff-tools-card">
                        <div class="card-body py-2 card-header-turquoise">
                            <div class="row align-items-center">
                                {{-- kolom search --}}
                                <div class="col-md-7 mb-2 mb-md-0">
                                    <div class="input-group input-group-sm" style="top: 7px">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                        </div>
                                        <input type="text" id="searchStaff"
                                               class="form-control form-control-sm"
                                               placeholder="Cari staff...">
                                    </div>
                                </div>

                                <div class="col-md-5 text-md-right">
                                    <button type="button" id="btnDetailAbsensi"
                                            class="btn btn-success btn-sm"
                                            style="position: relative; top:7px;"
                                            disabled>
                                        LIST DETAIL ABSENSI
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            {{-- deretan NAMA + tombol merah --}}
            <div id="staffContainer"></div>
        </div>
    @endif

    {{-- MODAL DETAIL ABSENSI PER SITUS --}}
    <div class="modal fade" id="detailAbsensiModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="detailAbsensiModalLabel">
                        Detail Absensi Situs: <span id="da_nama_situs"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-striped table-hover" id="detailAbsensiTable">
                        <thead>
                            <tr>
                                <th style="width:5%;">No</th>
                                <th>ID Admin</th>
                                <th>Nama Staff</th>
                                <th>Nama Situs</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Periode Cuti</th>
                                <th style="width:30%;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- isi via JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- POPUP NOTIFIKASI --}}
<div class="modal fade" id="notifModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div id="notifModalHeader" class="modal-header bg-success text-white py-2">
                <h6 class="modal-title mb-0" id="notifModalTitle">Informasi</h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body py-3">
                <p id="notifModalBody" class="mb-0"></p>
            </div>
        </div>
    </div>
</div>

@include('pages.hrdmanagement.new')

@push('scripts')
<script>
$(function () {
    // ===== ROUTES & ELEMENTS =====
    const routes = {
        staff : '{{ route('opforum.opabsensi.staff') }}',
        detail: '{{ route('opforum.opabsensi.detail') }}',
        save  : '{{ route('hrdmanagement.absensi.save') }}',
        stats : '{{ route('hsforum.opabsensi.stats') }}',
    };

    const $staffSection    = $('#staffSection');
    const $staffContainer  = $('#staffContainer');
    const $btnDetail       = $('#btnDetailAbsensi');
    const $searchStaff     = $('#searchStaff');
    const $detailModal     = $('#detailAbsensiModal');
    const $detailTableElem = $('#detailAbsensiTable');
    const $siteSelect      = $('#siteSelect');

    const $newModal        = $('#newAbsensiModal');
    const $newForm         = $('#newAbsensiForm');
    const $forceCreate     = $('#force_create');

        $newModal.on('change', 'input[name="kehadiran[]"]', function () {
        const $all = $newModal.find('input[name="kehadiran[]"]');

        // pastikan hanya satu yang terpilih
        $all.not(this).prop('checked', false);

        if (this.value === 'CUTI' && this.checked) {
            const defaultDate = $('#new_form_tanggal').val() || new Date().toISOString().slice(0, 10);

            $('#cutiDateRangeWrapper').show();
            $('#cuti_start').val(defaultDate);
            $('#cuti_end').val(defaultDate);
            $('#cuti_end').attr('min', defaultDate);
        } else {
            $('#cutiDateRangeWrapper').hide();
            $('#cuti_start').val('');
            $('#cuti_end').val('');
        }
    });

    let currentSiteIds  = [];
    let currentSiteName = '';
    let daTable         = null;

    // ===== INIT CHOICES (MULTI SELECT SITUS) =====
    if ($siteSelect.length) {
        new Choices($siteSelect[0], {
            removeItemButton: true,
            placeholderValue: 'Pilih situs...',
            searchPlaceholderValue: 'Cari situs...',
            shouldSort: false,
        });
    }

    // ===== LOAD SUMMARY (TOTAL SITUS & STAFF) =====
    $.getJSON(routes.stats)
        .done(function (res) {
            $('#card-total-sites').text(res.totalSites ?? '-');
            $('#card-total-staff').text(res.totalStaff ?? '-');
        })
        .fail(function () {
            $('#card-total-sites').text('-');
            $('#card-total-staff').text('-');
        });

    // ===== HELPER: RENDER STAFF PILL =====
    function renderStaffPills(list) {
        $staffContainer.empty();

        if (!list || !list.length) {
            $staffContainer.html('<p class="text-muted mb-0">Belum ada staff untuk situs ini.</p>');
            return;
        }

        const html = list.map(staff => `
            <div class="staff-pill-wrapper staff-open-modal"
                 data-id-admin="${staff.id_admin}"
                 data-nama-staff="${staff.nama_staff}"
                 data-id-situs="${staff.id_situs ?? ''}">
                <div class="staff-pill-name">
                    <div class="staff-id">${staff.id_admin}</div>
                    <div class="staff-name">${staff.nama_staff}</div>
                </div>
            </div>
        `).join('');

        $staffContainer.html(html);

        $staffContainer.find('.staff-pill-name').each(function () {
            this.scrollLeft = 0;
        });
    }

    // ===== HELPER: DATATABLE DETAIL =====
    function initDetailTable() {
        if (daTable) return daTable;

        daTable = $detailTableElem.DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            ordering: true,
            searching: true,
            autoWidth: false,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
            },
            columnDefs: [
                { targets: 0, width: '40px', className: 'text-center' },
                { targets: 7, className: 'col-remarks' }
            ]
        });

        return daTable;
    }

    function fillDetailTable(rows) {
        const table = initDetailTable();
        const data  = (rows || []).map((row, i) => {
            const cutiStart = row.cuti_start;
            const cutiEnd   = row.cuti_end;

            let cutiText = '-';
            if (cutiStart && cutiEnd) {
                cutiText = `${cutiStart} sd ${cutiEnd}`;
            }

            return [
                i + 1,
                row.id_admin   ?? '-',
                row.nama_staff ?? '-',
                row.nama_situs ?? '-',
                row.tanggal    ?? '-',
                row.status     ?? '-',
                cutiText,
                row.remarks    ?? '-',
            ];
        });

        table.clear();
        if (data.length) table.rows.add(data);
        table.draw();
    }

    // ===== HELPER: LOAD STAFF BY SITUS =====
    function loadStaffBySites(ids) {
        if (!ids.length) {
            $staffSection.hide();
            $btnDetail.prop('disabled', true);
            $staffContainer.empty();
            $('#opEmptyState').show();
            return;
        }

        $('#opEmptyState').hide();
        $staffSection.show();
        $btnDetail.prop('disabled', false);
        $searchStaff.val('');
        $staffContainer.html('<p class="text-muted mb-0">Memuat data staff...</p>');

        $.ajax({
            url   : routes.staff,
            method: 'GET',
            data  : { id_situs: ids },
            success(res) {
                renderStaffPills(res);
            },
            error(xhr) {
                console.log(xhr.responseText);
                $staffContainer.html('<p class="text-danger mb-0">Gagal memuat data staff.</p>');
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Gagal memuat data staff dari server.',
                });
            }
        });
    }

    // ===== HELPER: OPEN MODAL ABSENSI BARU =====
    function openNewAbsensiModal(idAdmin, namaStaff, idSitusRaw) {
        if ($newForm.length) {
            $newForm[0].reset();
        }

        $('#newCheckCuti').prop('checked', false);
        $('#cutiDateRangeWrapper').hide();
        $('#cuti_start, #cuti_end').val('');

        const allSiteIds = (idSitusRaw || '').toString().trim();

        $('#new_modal_id_admin').text(idAdmin);
        $('#new_modal_nama_staff').text(namaStaff);

        $('#new_form_id_admin').val(idAdmin);
        $('#new_form_nama_staff').val(namaStaff);
        $('#new_form_id_situs').val(allSiteIds);

        const today = new Date().toISOString().slice(0, 10);
        $('#new_form_tanggal').val(today);

        $forceCreate.val('0');

        $newModal.modal('show');
    }

    // ===== SUBMIT NEW ABSENSI VIA AJAX (OP ABSENSI) =====
    if ($newForm.length) {
        $newForm.on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                url   : routes.save,
                method: 'POST',
                data  : $newForm.serialize(),
                success(res) {
                    if (res && res.success) {
                        showPopup('success', res.message || 'Absensi berhasil disimpan!');

                        $newForm[0].reset();
                        $forceCreate.val('0');
                        $('#cutiDateRangeWrapper').hide();
                        $('#cuti_start, #cuti_end').val('');
                        $('#newAbsensiModal').modal('hide');

                        $('#hsforumTabs a[href="#cases"]').tab('show');
                    } else {
                        showPopup('error', (res && res.message) || 'Gagal menyimpan absensi.');
                    }
                },
                error(xhr) {
                    console.log(xhr.responseText);
                    showPopup('error', 'Terjadi kesalahan saat menyimpan absensi.');
                }
            });
        });
    }

    // ===== HELPER: LOAD DETAIL ABSENSI PER SITUS =====
    function loadDetailAbsensi(ids, siteName) {
        if (!ids.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih situs dulu',
                text: 'Silakan pilih minimal satu situs sebelum melihat detail absensi.',
            });
            return;
        }

        $('#da_nama_situs').text(siteName || 'Multiple Site');

        $.ajax({
            url   : routes.detail,
            method: 'GET',
            data  : { id_situs: ids },
            beforeSend() {
                const t = initDetailTable();
                t.clear().draw();
            },
            success(res) {
                fillDetailTable(res.data || res || []);
                $detailModal.modal('show');
            },
            error(xhr) {
                console.log(xhr.responseText);
                fillDetailTable([]);
                $detailModal.modal('show');
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Terjadi kesalahan saat memuat detail absensi.',
                });
            }
        });
    }

    // ===== EVENT: CHANGE SITUS =====
    $siteSelect.on('change', function () {
        const ids = $(this).val() || [];

        currentSiteIds  = ids;
        currentSiteName = $siteSelect.find('option:selected')
            .map(function () { return $(this).text().trim(); })
            .get()
            .join(', ');

        loadStaffBySites(ids);
    });

    // ===== EVENT: SEARCH STAFF LOKAL =====
    $searchStaff.on('keyup', function () {
        const q = $(this).val().toLowerCase();

        $('.staff-pill-wrapper').each(function () {
            const text = $(this).find('.staff-pill-name').text().toLowerCase();
            $(this).toggle(text.indexOf(q) !== -1);
        });
    });

    // ===== EVENT: KLIK PILL STAFF → MODAL ABSENSI BARU =====
    $(document).on('click', '.staff-open-modal', function () {
        const idAdmin   = $(this).data('id-admin');
        const namaStaff = $(this).data('nama-staff');
        const idSitus   = $(this).data('id-situs');

        openNewAbsensiModal(idAdmin, namaStaff, idSitus);
    });

    // ===== EVENT: TOMBOL LIST DETAIL ABSENSI =====
    $btnDetail.on('click', function () {
        loadDetailAbsensi(currentSiteIds, currentSiteName);
    });
});

// ===== POPUP NOTIFIKASI (SweetAlert) =====
function showPopup(type, message) {
    Swal.fire({
        icon: type === 'success' ? 'success' : 'error',
        title: type === 'success' ? 'Berhasil' : 'Gagal',
        text: message || '',
    });
}
</script>
@endpush
