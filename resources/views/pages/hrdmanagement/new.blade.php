<div class="modal fade" id="newAbsensiModal" tabindex="-1" role="dialog" aria-labelledby="newAbsensiModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {{-- FORM KHUSUS UNTUK SUBMIT DATA BARU (CREATE) --}}
            <form id="newAbsensiForm" action="{{ route('hrdmanagement.absensi.save') }}" method="POST">
                @csrf
                <input type="hidden" name="force_create" id="force_create" value="0">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="newAbsensiModalLabel">ABSENSI BARU</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    {{-- Hidden Fields --}}
                    <input type="hidden" name="id_admin" id="new_form_id_admin">
                    <input type="hidden" name="id_situs" id="new_form_id_situs">
                    <input type="hidden" name="nama_staff" id="new_form_nama_staff">

                    {{-- Display Info --}}
                    <p><strong>ID Admin :</strong> <span id="new_modal_id_admin"></span></p>
                    <p><strong>Nama Staff :</strong> <span id="new_modal_nama_staff"></span></p>

                    {{-- Tanggal Absensi --}}
                    <div class="form-group">
                        <label for="new_form_tanggal">Tanggal Absensi:</label>
                        <input
                            type="date"
                            class="form-control"
                            name="tanggal"
                            id="new_form_tanggal"
                            value="{{ date('Y-m-d') }}"
                            max="{{ date('Y-m-d') }}"
                            required
                        >
                    </div>

                    <hr>

                    <h6>Status Kehadiran:</h6>

                    {{-- Checkboxes --}}
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="newCheckTelat" name="kehadiran[]"
                                value="TELAT">
                            <label class="form-check-label" for="newCheckTelat">TELAT</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="newCheckSakit" name="kehadiran[]"
                                value="SAKIT">
                            <label class="form-check-label" for="newCheckSakit">SAKIT</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="newCheckIzin" name="kehadiran[]"
                                value="IZIN">
                            <label class="form-check-label" for="newCheckIzin">IZIN</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="newCheckTanpaKabar" name="kehadiran[]"
                                value="TANPA KABAR">
                            <label class="form-check-label" for="newCheckTanpaKabar">TANPA KABAR</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="newCheckCuti" name="kehadiran[]"
                                value="CUTI">
                            <label class="form-check-label" for="newCheckCuti">CUTI</label>
                        </div>
                    </div>

                    {{-- Periode Cuti (muncul hanya jika CUTI dicentang) --}}
                    <div class="form-group" id="cutiDateRangeWrapper" style="display:none;">
                        <label><strong>Periode Cuti:</strong></label>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="cuti_start">Dari tanggal</label>
                                <input type="date" class="form-control" id="cuti_start" name="cuti_start">
                            </div>
                            <div class="col-md-6">
                                <label for="cuti_end">Sampai tanggal</label>
                                <input type="date" class="form-control" id="cuti_end" name="cuti_end">
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Contoh: cuti 7–14 hari (sesuai kebijakan perusahaan).
                        </small>
                    </div>

                    <hr>

                    {{-- Remarks --}}
                    <div class="form-group">
                        <label for="newInputRemarks">Catatan (Remarks):</label>
                        <textarea class="form-control" id="newInputRemarks" name="remarks" rows="3"
                            placeholder="Masukkan catatan atau keterangan tambahan..."></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">SUBMIT</button>
                </div>
            </form>
        </div>
    </div>
</div>
