@extends('layouts.app')

@section('content')
<div class="card">
  <div class="card-header d-flex align-items-center">
    <div>
      <h5 class="mb-0">Daftar Topik</h5>
      <small class="text-muted">Data Topik</small>
    </div>

    <a class="btn btn-primary ml-auto" href="{{ route('topik.create') }}">
      Tambah Topik Baru
    </a>
  </div>

  <div class="card-body">
    @if(session('ok'))
      <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <form method="get" class="mb-3 d-flex" action="{{ route('topik.index') }}">
      <input name="q" value="{{ $search }}" class="form-control mr-2" placeholder="Search...">
      <button class="btn btn-outline-secondary">Cari</button>
    </form>

    <div class="table-responsive no-rwd-labels">
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
          @forelse($topik as $i => $row)
            <tr>
              <td>{{ ($topik->currentPage()-1)*$topik->perPage() + $i + 1 }}</td>
              <td>{{ $row->topik_title }}</td>
              <td>
                <span class="badge {{ $row->status ? 'bg-success' : 'bg-secondary' }}">
                  {{ $row->status_label }}
                </span>
                </td>
              <td>
                <a href="{{ route('topik.edit',$row) }}" class="btn btn-warning btn-sm">Edit</a>
                <form method="post" action="{{ route('topik.destroy',$row) }}" class="d-inline"
                      onsubmit="return confirm('Hapus topik ini?')">
                  @csrf @method('delete')
                  <button class="btn btn-danger btn-sm">Hapus</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-muted">Belum ada data.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination kustom: tanpa panah « », dan "Showing..." diletakkan di bawah --}}
    <div class="mt-3">
      {{ $topik->onEachSide(1)->links('pagination.topik') }}
    </div>
  </div>
</div>
@endsection
