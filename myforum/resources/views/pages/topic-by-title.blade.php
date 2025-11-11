@extends('layouts.app')
@section('title','HS Forum (Auditor)')

@section('breadcrumb')
  <ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Beranda</a></li>
    <li class="breadcrumb-item active">Data Cases — {{ $topic }}</li>
  </ol>
@endsection

@push('styles')
<style>
  .dataTables_wrapper .dataTables_filter { float: right; text-align: right; }
  .table td, .table th { vertical-align: middle; }
  .badge-status { font-weight: 600; }
</style>
@endpush

@section('content')
  <div class="card">
    <div class="card-header d-flex align-items-center">
      <div>
        <h3 class="card-title mb-0">Data Cases — {{ $topic }}</h3>
        <div class="small text-muted mt-1">
          Periode: <code>{{ $periodeAktif }}</code> • Scope status: <em>{{ $statusLabel }}</em>
        </div>
      </div>
      <a href="{{ route('dashboard.index', ['tab' => 'topik_title']) }}" class="btn btn-light ml-auto">← Kembali</a>
    </div>

    <div class="card-body">
      <table id="topicFullTable" class="table table-striped table-bordered w-100">
        <thead>
        <tr>
          <th style="width:60px">#</th>
          <th style="width:120px">Topik ID</th>
          <th>Topik</th>
          <th style="width:220px">Dibuat Untuk</th>
          <th style="width:170px">Tanggal Topik</th>
          <th style="width:140px">Status Topik</th>
          <th style="width:130px">{{ $colIdLabel }}</th>
          <th style="width:110px">Action</th>
        </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  $(function () {
    const toBadge = (s) => {
      switch ((s || '').toLowerCase().trim()) {
        case 'open':        return '<span class="badge badge-primary badge-status">Open</span>';
        case 'pending':     return '<span class="badge badge-warning text-dark badge-status">Pending</span>';
        case 'on progress': return '<span class="badge badge-info badge-status">On&nbsp;Progress</span>';
        case 'close':       return '<span class="badge badge-success badge-status">Close</span>';
        default:            return s || '-';
      }
    };

    // ===== variabel dari Controller =====
    const auditorDetailUrlTemplate = @json($auditorDetailUrlTemplate);
    const dtAjaxUrl    = @json($dtAjaxUrl);
    const dtAjaxParams = @json($dtAjaxParams);

    $('#topicFullTable').DataTable({
      processing: true,
      serverSide: false,
      searching: true,
      paging: true,
      ordering: true,
      ajax: {
        url: dtAjaxUrl,
        data: dtAjaxParams,
        dataSrc: 'data'
      },
      order: [[4, 'desc']],
      columns: [
        { data: null, className: 'text-center', render: (d,t,r,m) => m.row + 1 },
        { data: 'topik_id' },
        { data: 'topik_title', defaultContent: '-' },
        { data: 'created_for_name', defaultContent: '-' },
        { data: 'created_at' },
        { data: 'status_text', render: (v) => toBadge(v) },
        { data: null, render: (row) => row.id_auditor ?? row.id_admin ?? '' },

        // >>>>> ACTION: tombol Comment selalu tampil
        {
          data: null, orderable: false, searchable: false,
          render: (row) => {
            const st = String(row.status_text || '').toLowerCase().trim();
            const isClosed = st === 'close' || Number(row.status_code) === 4;

            const slug = row.slug ?? row.topik_id;
            let href   = auditorDetailUrlTemplate.replace('__SLUG__', encodeURIComponent(slug));

            // param ke halaman detail:
            // - closed       -> comment=0 (form disembunyikan)
            // - non-closed   -> comment=1 + auto-scroll ke form
            const params = new URLSearchParams({
              from: 'dashboard',
              comment: isClosed ? 0 : 1
            }).toString();

            href += (href.includes('?') ? '&' : '?') + params + (isClosed ? '' : '#comment-form');

            const cls   = 'btn btn-warning btn-sm';
            const title = isClosed
              ? 'Topik close: form komentar dinonaktifkan'
              : 'Tambah komentar';

            return `<a class="${cls}" href="${href}" title="${title}">📝 Comment</a>`;
          }
        }
        // <<<<< END ACTION
      ]
    });
  });
</script>
@endpush
