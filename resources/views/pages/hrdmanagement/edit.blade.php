@push('styles')
<style>
    /* Semua kolom default: satu baris, kalau kepanjangan di-ellipsis */
    #absensiListTable th,
    #absensiListTable td {
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }

    /* Khusus kolom Catatan (Remarks) boleh multi-baris */
    #absensiListTable td.col-remarks {
        white-space: normal !important;
        word-wrap: break-word;
        word-break: break-word;
        max-width: 260px; /* boleh sesuaikan */
    }
</style>
@endpush


{{-- MODAL LIST ABSENSI (PILIH DULU DATA YANG MAU DI-EDIT) --}}
<div class="modal fade" id="listAbsensiModal" tabindex="-1" role="dialog" aria-labelledby="listAbsensiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="listAbsensiModalLabel">PILIH DATA ABSENSI UNTUK EDIT</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <p>
                    <strong>ID Admin :</strong> <span id="list_modal_id_admin"></span><br>
                    <strong>Nama Staff :</strong> <span id="list_modal_nama_staff"></span>
                </p>
                <hr>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-hover" id="absensiListTable">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 5%;">No.</th>
                                <th style="width: 15%;">Tanggal</th>
                                <th style="width: 10%;">Nama Situs</th>
                                <th style="width: 10%;">Status Kehadiran</th>
                                <th style="width: 20%;">Periode Cuti</th>
                                <th style="width: 30%;">Catatan</th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Diisi via JS --}}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDIT ABSENSI --}}
<div class="modal fade" id="editAbsensiModal" tabindex="-1" role="dialog" aria-labelledby="editAbsensiModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editAbsensiForm" action="" method="POST">
                @csrf
                <input type="hidden" name="_method" value="PUT">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="editAbsensiModalLabel">EDIT ABSENSI</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    {{-- Hidden --}}
                    <input type="hidden" name="id_admin" id="edit_form_id_admin">
                    <input type="hidden" name="id_situs" id="edit_form_id_situs">
                    <input type="hidden" name="nama_staff" id="edit_form_nama_staff">

                    {{-- Info --}}
                    <p><strong>ID Admin :</strong> <span id="edit_modal_id_admin"></span></p>
                    <p><strong>Nama Staff :</strong> <span id="edit_modal_nama_staff"></span></p>

                    <div class="form-group">
                        <label for="edit_form_tanggal"><strong>Tanggal Absensi :</strong></label>
                        <input type="date"
                               class="form-control"
                               name="tanggal"
                               id="edit_form_tanggal"
                               max="{{ date('Y-m-d') }}">
                    </div>

                    <hr>

                    <h6>Status Kehadiran:</h6>
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input edit-kehadiran-check" type="checkbox" id="editCheckTelat" name="kehadiran[]" value="TELAT">
                            <label class="form-check-label" for="editCheckTelat">TELAT</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input edit-kehadiran-check" type="checkbox" id="editCheckSakit" name="kehadiran[]" value="SAKIT">
                            <label class="form-check-label" for="editCheckSakit">SAKIT</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input edit-kehadiran-check" type="checkbox" id="editCheckIzin" name="kehadiran[]" value="IZIN">
                            <label class="form-check-label" for="editCheckIzin">IZIN</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input edit-kehadiran-check" type="checkbox" id="editCheckTanpaKabar" name="kehadiran[]" value="TANPA KABAR">
                            <label class="form-check-label" for="editCheckTanpaKabar">TANPA KABAR</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input edit-kehadiran-check" type="checkbox" id="editCheckCuti" name="kehadiran[]" value="CUTI">
                            <label class="form-check-label" for="editCheckCuti">CUTI</label>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group" id="editCutiDateRangeWrapper" style="display:none;">
                        <label><strong>Periode Cuti:</strong></label>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="edit_cuti_start">Dari tanggal</label>
                                <input type="date" class="form-control" id="edit_cuti_start" name="cuti_start">
                            </div>
                            <div class="col-md-6">
                                <label for="edit_cuti_end">Sampai tanggal</label>
                                <input type="date" class="form-control" id="edit_cuti_end" name="cuti_end">
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Isi jika status = CUTI dan memakai rentang tanggal.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="editInputRemarks">Catatan (Remarks):</label>
                        <textarea class="form-control" id="editInputRemarks" name="remarks" rows="3"
                                  placeholder="Masukkan catatan atau keterangan tambahan..."required></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">UPDATE</button>
                </div>
            </form>
        </div>
    </div>
</div>
