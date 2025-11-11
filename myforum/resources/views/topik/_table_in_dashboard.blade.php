<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Daftar Topik</h5>
  <button class="btn btn-primary" data-toggle="modal" data-target="#modalTopikCreate">
    Tambah Topik
  </button>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead>
      <tr>
        <th style="width:70px">#</th>
        <th>Topik</th>
        <th style="width:120px">Status</th>
        <th style="width:160px">Action</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $row)
        <tr>
          <td>{{ $row['no'] }}</td>
          <td>{{ $row['topik_title'] }}</td>
          <td>
            <form action="{{ $row['toggleUrl'] }}" method="post">
              @csrf @method('patch')
              <button class="btn btn-sm {{ $row['statusClass'] }}">
                {{ $row['statusLabel'] }}
              </button>
            </form>
          </td>
          <td>
            <a href="{{ $row['editUrl'] }}" class="btn btn-warning btn-sm">Edit</a>
            <form action="{{ $row['destroyUrl'] }}" method="post" class="d-inline"
                  onsubmit="return confirm('Hapus topik ini?')">
              @csrf @method('delete')
              <button class="btn btn-danger btn-sm">Hapus</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="text-center text-muted">Belum ada data.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

{{ $topik->onEachSide(1)->links() }}

{{-- MODAL CREATE --}}
<div class="modal fade" id="modalTopikCreate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Topik</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="post" action="{{ $storeUrl }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Topik <span class="text-danger">*</span></label>
            <input
              type="text"
              name="topik_title"
              class="form-control @error('topik_title') is-invalid @enderror"
              value="{{ old('topik_title') }}"
              required
              placeholder="Isi dengan topik">
            @error('topik_title')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-control">
              <option value="1" {{ old('status','1')=='1' ? 'selected' : '' }}>Aktif</option>
              <option value="0" {{ old('status')=='0' ? 'selected' : '' }}>Nonaktif</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>
</div>
