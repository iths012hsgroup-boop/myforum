@extends('layouts.app')
@section('title', 'HRD Management')

@push('styles')
<style>
    .table-hover > tbody > tr:hover {
        background-color: #fffac2 !important;
    }

    .dataTables_wrapper .row {
        margin-bottom: 15px;
    }

    /* Kolom Remarks di list absensi (modal list) */
    #absensiListTable td.col-remarks {
        max-width: 250px;
        white-space: normal;
        word-wrap: break-word;
        word-break: break-word;
    }
</style>
@endpush

@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('hrdmanagement.grafik') }}">Kembali</a></li>
        <li class="breadcrumb-item active">Tambah Absensi</li>
    </ol>
@endsection

@section('content')
    {{-- Dipakai JS untuk default tanggal --}}
    <input type="hidden" id="tanggal_hari_ini" value="{{ now()->toDateString() }}">

    <div class="card table-responsive">
        <div class="card-header">
            <div class="row w-100">
                <div class="col-md-6 d-flex align-items-center">
                    <h3 class="card-title m-0 font-weight-bold">
                        Hello, {{ Auth::user()->id_admin }} / {{ Auth::user()->nama_staff }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if ($filteredUsers->isEmpty())
                <p>Tidak ada data staff yang terhubung dengan situs ini.</p>
            @endif

            <table class="table table-bordered table-striped table-hover" id="staffTable">
                <thead>
                    <tr>
                        <th style="width: 3%;" class="text-center">No.</th>
                        <th style="width: 30%;">ID Admin</th>
                        <th>Nama Staff</th>
                        <th style="width: 15%;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Diisi DataTables via Ajax --}}
                </tbody>
            </table>
        </div>

        {{-- MODAL DETAIL RIWAYAT PER STAFF --}}
        <div class="modal fade" id="detailAbsensiModal" tabindex="-1" role="dialog"
             aria-labelledby="detailAbsensiModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="detailAbsensiModalLabel">
                            Riwayat Absensi: <span id="detail_nama_staff"></span>
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
                                    <th>Tanggal</th>
                                    <th>Nama Situs</th>
                                    <th>Status</th>
                                    <th>Periode Cuti</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Diisi via JS --}}
                            </tbody>
                        </table>
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

        {{-- Modal2 pendukung --}}
        @include('pages.hrdmanagement.edit')
        @include('pages.hrdmanagement.new')
@endsection

@push('scripts')
<script>
    $(function () {
        // ================== ROUTES & CONST ==================
        const routes = {
            save      : '{{ route('hrdmanagement.absensi.save') }}',
            updateTpl : '{{ route('hrdmanagement.absensi.update', ['id' => 'ABSENSI_ID']) }}',
            list      : '{{ route('hrdmanagement.absensi.list') }}',
            destroyTpl: '{{ route('hrdmanagement.absensi.destroy', ['id' => 'ABSENSI_ID']) }}',
            staffData : '{{ route('hrdmanagement.staff.data') }}',
            detail    : '{{ route('hrdmanagement.absensi.detail') }}',
            checkDup  : '{{ route('hrdmanagement.absensi.check_duplicate') }}',
        };

        const today            = $('#tanggal_hari_ini').val() || new Date().toISOString().slice(0, 10);
        const $staffTable      = $('#staffTable');
        const $detailModal     = $('#detailAbsensiModal');

        const $cutiWrapperNew  = $('#cutiDateRangeWrapper');
        const $cutiStartNew    = $('#cuti_start');
        const $cutiEndNew      = $('#cuti_end');

        const $cutiWrapperEdit = $('#editCutiDateRangeWrapper');
        const $cutiStartEdit   = $('#edit_cuti_start');
        const $cutiEndEdit     = $('#edit_cuti_end');

        const $newForm         = $('#newAbsensiForm');
        const $forceInput      = $('#force_create');

        let detailTable        = null;

        const dtLang = {
            lengthMenu : 'Tampilkan _MENU_ data per halaman',
            zeroRecords: 'Tidak ada data yang ditemukan',
            info       : 'Menampilkan halaman _PAGE_ dari _PAGES_',
            infoEmpty  : 'Tidak ada data',
            infoFiltered: '(difilter dari total _MAX_ data)',
            search     : 'Cari:',
            paginate   : {
                first   : 'Awal',
                last    : 'Akhir',
                next    : 'Berikutnya',
                previous: 'Sebelumnya',
            },
        };

        // ================== HELPER UI ==================
        function hideCutiRange($wrapper, $start, $end) {
            $wrapper.hide();
            $start.val('');
            $end.val('');
        }

        function showCutiRange($wrapper, $start, $end, defaultDate) {
            $wrapper.show();
            $start.val(defaultDate);
            $end.val(defaultDate);
            $end.attr('min', defaultDate);
        }

        function buildUpdateUrl(id) {
            return routes.updateTpl.replace('ABSENSI_ID', id);
        }

        function buildDeleteUrl(id) {
            return routes.destroyTpl.replace('ABSENSI_ID', id);
        }

        function setEditFormBase(absensiId, data) {
            const $form = $('#editAbsensiForm');
            $form.attr('action', buildUpdateUrl(absensiId));
            $form[0].reset();
            $('.edit-kehadiran-check').prop('checked', false);

            $('#edit_modal_id_admin').text(data.idAdmin);
            $('#edit_modal_nama_staff').text(data.namaStaff);

            $('#edit_form_id_admin').val(data.idAdmin);
            $('#edit_form_nama_staff').val(data.namaStaff);
            $('#edit_form_id_situs').val(data.idSitus);
            $('#edit_form_tanggal').val(data.tanggal);
            $('#editInputRemarks').val(data.remarks || '');
        }

        function applyStatusToEditForm(statusStr, cutiStart, cutiEnd, tanggal) {
            const status = (statusStr || '').toUpperCase();
            const isCuti = status.includes('CUTI') || cutiStart || cutiEnd;

            $('.edit-kehadiran-check').prop('checked', false);
            hideCutiRange($cutiWrapperEdit, $cutiStartEdit, $cutiEndEdit);

            if (isCuti) {
                $('#editCheckCuti').prop('checked', true);
                $cutiWrapperEdit.show();

                const startVal = cutiStart || tanggal;
                const endVal   = cutiEnd || tanggal;

                $cutiStartEdit.val(startVal);
                $cutiEndEdit.val(endVal);
                $cutiEndEdit.attr('min', startVal);
                return;
            }

            if (status) {
                const firstStatus = status.split(',')[0].trim();
                $('.edit-kehadiran-check').each(function () {
                    $(this).prop('checked', $(this).val().toUpperCase() === firstStatus);
                });
            }
        }

        function initDetailAbsensiTable() {
            if (detailTable) return detailTable;

            detailTable = $('#detailAbsensiTable').DataTable({
                processing : true,
                serverSide : false,
                paging     : true,
                lengthChange: true,
                searching  : true,
                ordering   : true,
                info       : true,
                autoWidth  : false,
                language   : dtLang,
                columnDefs : [
                    { targets: 0, width: '40px', className: 'text-center' },
                ],
            });

            return detailTable;
        }

        function submitNewAbsensiAjax() {
            if (!$newForm.length) return;

            // 🔹 WAJIB pilih kehadiran
            const checkedNew = $('#newAbsensiModal input[name="kehadiran[]"]:checked').length;
            if (!checkedNew) {
                showPopup('error', 'Pilih minimal satu status kehadiran.');
                return;
            }

            // 🔹 WAJIB isi remarks
            const remarksNew = $('#newInputRemarks').val().trim();
            if (!remarksNew) {
                showPopup('error', 'Remarks / catatan wajib diisi.');
                return;
            }

            $.ajax({
                url   : routes.save,
                method: 'POST',
                data  : $newForm.serialize(),
                success(res) {
                    if (res && res.success) {
                        showPopup('success', res.message || 'Absensi berhasil disimpan!');
                        // kalau mau: reset form & tutup modal
                        // $newForm[0].reset();
                        // $('#newAbsensiModal').modal('hide');
                    } else {
                        showPopup('error', (res && res.message) || 'Gagal menyimpan absensi.');
                    }
                },
                error(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        let msgs = [];
                        Object.values(xhr.responseJSON.errors).forEach(arr => msgs = msgs.concat(arr));
                        showPopup('error', 'Validasi gagal: ' + msgs.join(' | '));
                    } else {
                        showPopup('error', 'Terjadi kesalahan di server saat menyimpan absensi.');
                    }
                }
            });
        }

        // ================== NEW ABSENSI (DUPLICATE CHECK) ==================
        if ($newForm.length) {
            $newForm.on('submit', function (e) {
                e.preventDefault();

                const idAdmin   = $('#new_form_id_admin').val();
                const namaStaff = $('#new_form_nama_staff').val();
                const tanggal   = $('#new_form_tanggal').val();

                // sudah konfirmasi duplikat sebelumnya
                if ($forceInput.val() === '1') {
                    submitNewAbsensiAjax();
                    return;
                }

                if (!idAdmin || !tanggal) {
                    submitNewAbsensiAjax();
                    return;
                }

                $.getJSON(routes.checkDup, { id_admin: idAdmin, tanggal })
                    .done(function (res) {
                        if (res && res.exists) {
                            const msg =
                                `Data absensi untuk:\n` +
                                `ID Admin : ${idAdmin}\n` +
                                `Nama     : ${namaStaff}\n` +
                                `Tanggal  : ${tanggal}\n\n` +
                                `SUDAH ADA.\n` +
                                `Tetap tambahkan lagi?`;

                            Swal.fire({
                                icon: 'warning',
                                title: 'Duplikat absensi',
                                text: msg,
                                showCancelButton: true,
                                confirmButtonText: 'Ya, tetap simpan',
                                cancelButtonText : 'Batal'
                            }).then(result => {
                                if (result.isConfirmed) {
                                    $forceInput.val('1');
                                    submitNewAbsensiAjax();
                                }
                            });
                        } else {
                            submitNewAbsensiAjax();
                        }
                    })
                    .fail(function () {
                        // kalau cek duplikat error → tetap izinkan simpan
                        submitNewAbsensiAjax();
                    });
            });
        }

        // ================== DATATABLE STAFF ==================
        const staffTable = $staffTable.DataTable({
            processing  : true,
            serverSide  : false,
            ajax        : routes.staffData,
            columns     : [
                {
                    data      : null,
                    orderable : false,
                    searchable: false,
                    render    : (data, type, row, meta) => meta.row + 1,
                },
                { data: 'id_admin',   name: 'id_admin'   },
                { data: 'nama_staff', name: 'nama_staff' },
                {
                    data      : null,
                    orderable : false,
                    searchable: false,
                    className : 'text-center',
                    render    : (data, type, row) => `
                        <button type="button"
                            class="btn btn-primary btn-sm absensi-baru-btn"
                            data-id-admin="${row.id_admin}"
                            data-nama-staff="${row.nama_staff}"
                            data-id-situs-user="${row.id_situs}"
                            title="Absensi Baru Hari Ini">
                            <i class="fas fa-plus"></i> Absensi
                        </button>

                        <button type="button"
                            class="btn btn-warning btn-sm edit-absensi-btn"
                            data-id-admin="${row.id_admin}"
                            data-nama-staff="${row.nama_staff}"
                            data-id-situs-user="${row.id_situs}"
                            title="Edit Absensi Hari Ini">
                            <i class="fas fa-pencil-alt"></i> Edit
                        </button>

                        <button type="button"
                            class="btn btn-info btn-sm detail-absensi-btn"
                            data-id-admin="${row.id_admin}"
                            data-nama-staff="${row.nama_staff}"
                            data-id-situs-user="${row.id_situs}"
                            title="Lihat Riwayat Absensi">
                            <i class="fas fa-list"></i> Details
                        </button>
                    `,
                },
            ],
            responsive  : true,
            paging      : true,
            lengthChange: true,
            searching   : true,
            ordering    : true,
            info        : true,
            autoWidth   : false,
            language    : dtLang,
        });

        // ================== DETAIL ABSENSI (RIWAYAT) ==================
        $(document).on('click', '.detail-absensi-btn', function () {
            const idAdmin = $(this).data('id-admin');
            const nama    = $(this).data('nama-staff');

            $('#detail_nama_staff').text(nama);

            const dt = initDetailAbsensiTable();
            dt.clear().draw();
            dt.row.add(['', 'Loading...', '', '', '', '']).draw();

            $.ajax({
                url   : routes.detail,
                method: 'GET',
                data  : { id_admin: idAdmin },
                success(res) {
                    dt.clear();

                    if (!res || !res.length) {
                        dt.row.add(['', 'Tidak ada data absensi.', '', '', '']).draw();
                        return;
                    }

                    const data = res.map((row, i) => {
                        const cutiStart = row.cuti_start;
                        const cutiEnd   = row.cuti_end;

                        let cutiText = '-';
                        if (cutiStart && cutiEnd) {
                            cutiText = `${cutiStart} sd ${cutiEnd}`;
                        }

                        return [
                            i + 1,
                            row.tanggal    ?? '-',
                            row.nama_situs ?? '-',
                            row.status     ?? '-',
                            cutiText,
                            row.remarks    ?? '-',
                        ];
                    });

                    dt.rows.add(data).draw();
                },
                error(xhr) {
                    console.log('ERROR DETAIL ABSENSI:', xhr.responseText);
                    dt.clear();
                    dt.row.add(['', 'Terjadi kesalahan saat memuat data.', '', '', '']).draw();
                },
            });

            $detailModal.modal('show');
        });

        // ================== ABSENSI BARU ==================
        $staffTable.on('click', '.absensi-baru-btn', function (e) {
            e.stopPropagation();

            const idAdmin   = $(this).data('id-admin');
            const namaStaff = $(this).data('nama-staff');
            const idSitusAll= $(this).data('id-situs-user'); // "4,34"

            $('#newAbsensiForm')[0].reset();
            hideCutiRange($cutiWrapperNew, $cutiStartNew, $cutiEndNew);
            $('#newAbsensiModal input[name="kehadiran[]"]').prop('checked', false);

            if (!idSitusAll) {
                alert('Gagal: ID Situs tidak valid.');
                return;
            }

            $('#new_modal_id_admin').text(idAdmin);
            $('#new_modal_nama_staff').text(namaStaff);
            $('#new_form_id_admin').val(idAdmin);
            $('#new_form_nama_staff').val(namaStaff);
            $('#new_form_id_situs').val(idSitusAll);
            $('#new_form_tanggal').val(today);

            $('#newAbsensiModal').modal('show');
        });

        // ================== LIST & EDIT ABSENSI ==================
        $staffTable.on('click', '.edit-absensi-btn', function (e) {
            e.stopPropagation();

            const button      = $(this);
            const idAdmin     = button.data('id-admin');
            const namaStaff   = button.data('nama-staff');
            const idSitusUser = button.data('id-situs-user');

            if (!idSitusUser) {
                alert('Gagal: ID Situs tidak ditemukan.');
                return;
            }

            button.prop('disabled', true)
                  .html('<i class="fas fa-spinner fa-spin"></i> Loading...');

            $.ajax({
                url : routes.list,
                type: 'GET',
                data: { id_admin: idAdmin, id_situs: idSitusUser },
                success(response) {
                    const $tbody = $('#absensiListTable tbody');
                    $tbody.empty();

                    if (!response || !response.length) {
                        Swal.fire({
                            icon : 'info',
                            title: 'Tidak ada data',
                            text : 'Tidak ada data absensi yang ditemukan untuk staff ini.',
                        });
                        return;
                    }

                    $('#list_modal_id_admin').text(idAdmin);
                    $('#list_modal_nama_staff').text(namaStaff);

                    response.forEach((row, index) => {
                        const tanggal   = (row.tanggal || '').toString().substring(0, 10);
                        const status    = row.status || '';
                        const remarks   = row.remarks || '';
                        const namaSitus = row.nama_situs || '-';
                        const cutiStart = row.cuti_start;
                        const cutiEnd   = row.cuti_end;

                        let cutiText = '-';
                        if (cutiStart && cutiEnd) {
                            cutiText = `${cutiStart} sd ${cutiEnd}`;
                        }

                        $tbody.append(`
                            <tr>
                                <td>${index + 1}</td>
                                <td>${tanggal}</td>
                                <td>${namaSitus}</td>
                                <td>${status}</td>
                                <td>${cutiText}</td>
                                <td class="col-remarks">${remarks}</td>
                                <td class="text-nowrap">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button"
                                            class="btn btn-warning pilih-absensi-btn"
                                            data-absensi-id="${row.id}"
                                            data-tanggal="${tanggal}"
                                            data-status="${status}"
                                            data-remarks="${remarks}"
                                            data-id-admin="${idAdmin}"
                                            data-nama-staff="${namaStaff}"
                                            data-id-situs="${row.id_situs}"
                                            data-cuti-start="${row.cuti_start}"
                                            data-cuti-end="${row.cuti_end}">
                                            Edit
                                        </button>
                                        <button type="button"
                                            class="btn btn-danger delete-absensi-btn"
                                            data-absensi-id="${row.id}">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `);
                    });

                    $('#listAbsensiModal').modal('show');
                },
                error() {
                    alert('Gagal mengambil data absensi dari server.');
                },
                complete() {
                    button.prop('disabled', false)
                          .html('<i class="fas fa-pencil-alt"></i> Edit');
                },
            });
        });

        $(document).on('click', '.pilih-absensi-btn', function () {
            const btn       = $(this);
            const absensiId = btn.data('absensi-id');
            const idAdmin   = btn.data('id-admin');
            const namaStaff = btn.data('nama-staff');
            const idSitus   = btn.data('id-situs');
            const tanggal   = btn.data('tanggal');
            const statusStr = (btn.data('status') || '').toString();
            const remarks   = btn.data('remarks') || '';
            const cutiStart = btn.data('cuti-start') || '';
            const cutiEnd   = btn.data('cuti-end') || '';

            setEditFormBase(absensiId, { idAdmin, namaStaff, idSitus, tanggal, remarks });
            applyStatusToEditForm(statusStr, cutiStart, cutiEnd, tanggal);

            $('#listAbsensiModal').modal('hide');
            $('#editAbsensiModal').modal('show');
        });

        $(document).on('submit', '#editAbsensiForm', function (e) {
            e.preventDefault();

            // 🔹 WAJIB pilih kehadiran
            const checkedEdit = $('#editAbsensiModal input[name="kehadiran[]"]:checked').length;
            if (!checkedEdit) {
                showPopup('error', 'Pilih minimal satu status kehadiran.');
                return;
            }

            // 🔹 WAJIB isi remarks
            const remarksEdit = $('#editInputRemarks').val().trim();
            if (!remarksEdit) {
                showPopup('error', 'Remarks / catatan wajib diisi.');
                return;
            }

            const $form = $(this);
            const url   = $form.attr('action');

            $.ajax({
                url   : url,
                method: 'POST', // _method=PUT di form
                data  : $form.serialize(),
                success(res) {
                    if (res && res.success) {
                        showPopup('success', res.message || 'Data absensi berhasil diupdate.');
                        $('#editAbsensiModal').modal('hide');
                    } else {
                        showPopup('error', (res && res.message) || 'Gagal mengupdate data absensi.');
                    }
                },
                error(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        let msgs = [];
                        Object.values(xhr.responseJSON.errors).forEach(arr => msgs = msgs.concat(arr));
                        showPopup('error', 'Validasi gagal: ' + msgs.join(' | '));
                    } else {
                        showPopup('error', 'Terjadi kesalahan di server saat mengupdate absensi.');
                    }
                }
            });
        });

        // ================== TOGGLE CUTI (NEW & EDIT) ==================
        $('#newAbsensiModal').on('change', 'input[name="kehadiran[]"]', function () {
            const $all = $('#newAbsensiModal input[name="kehadiran[]"]');
            $all.not(this).prop('checked', false);

            if (this.value === 'CUTI' && this.checked) {
                const defaultDate = $('#new_form_tanggal').val() || today;
                showCutiRange($cutiWrapperNew, $cutiStartNew, $cutiEndNew, defaultDate);
            } else {
                hideCutiRange($cutiWrapperNew, $cutiStartNew, $cutiEndNew);
            }
        });

        $('#editAbsensiModal').on('change', 'input[name="kehadiran[]"]', function () {
            const $all = $('#editAbsensiModal input[name="kehadiran[]"]');
            $all.not(this).prop('checked', false);

            if (this.value === 'CUTI' && this.checked) {
                const defaultDate = $('#edit_form_tanggal').val() || today;
                showCutiRange($cutiWrapperEdit, $cutiStartEdit, $cutiEndEdit, defaultDate);
            } else {
                hideCutiRange($cutiWrapperEdit, $cutiStartEdit, $cutiEndEdit);
            }
        });

        // ================== DELETE ABSENSI ==================
        $(document).on('click', '.delete-absensi-btn', function () {
            const btn       = $(this);
            const absensiId = btn.data('absensi-id');

            Swal.fire({
                title: 'Yakin hapus?',
                text : 'Data absensi ini akan dihapus permanen.',
                icon : 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText : 'Batal'
            }).then(result => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url : buildDeleteUrl(absensiId),
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token : '{{ csrf_token() }}',
                    },
                    success(response) {
                        if (response && response.success) {
                            btn.closest('tr').remove();
                            showPopup('success', response.message || 'Data absensi berhasil dihapus.');
                        } else {
                            showPopup('error', response.message || 'Gagal menghapus data absensi.');
                        }
                    },
                    error() {
                        showPopup('error', 'Terjadi kesalahan saat menghapus data absensi di server.');
                    },
                });
            });
        });

        // ================== BATAS TANGGAL EDIT ==================
        const editDateInput = document.getElementById('edit_form_tanggal');
        if (editDateInput) {
            editDateInput.setAttribute('max', today);
            editDateInput.addEventListener('input', function () {
                if (this.value > today) {
                    alert('Tanggal tidak boleh melebihi hari ini.');
                    this.value = today;
                }
            });
        }
    });

    // ================== POPUP SWEETALERT ==================
    function showPopup(type, message) {
        Swal.fire({
            icon : type === 'success' ? 'success' : 'error',
            title: type === 'success' ? 'Berhasil' : 'Gagal',
            text : message || '',
            timer: 2000,
            showConfirmButton: false
        });
    }
</script>
@endpush
