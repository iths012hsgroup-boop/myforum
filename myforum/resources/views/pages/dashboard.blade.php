@extends('layouts.app')
@section('title', 'Dashboard')

@push('styles')
<style>
  .gap-2 { gap:.5rem; }

  /* ====== TOPIK (tetap) ====== */
  .stat-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
    gap:16px;
  }
  .stat-card{
    border-radius:14px;
    padding:16px 16px 56px;
    min-height:150px;
    position:relative;
    box-shadow:0 6px 20px rgba(0,0,0,.06);
    border:1px solid transparent;
    transition:transform .08s ease-in-out, box-shadow .08s ease-in-out;
  }
  .stat-card:hover{ transform:translateY(-2px); box-shadow:0 10px 24px rgba(0,0,0,.08); }
  .stat-card .count{ font-size:40px; font-weight:800; line-height:1; }
  .stat-card .title{ text-transform:uppercase; letter-spacing:.3px; font-weight:600; opacity:.9; margin-top:6px; }
  .more-strip{
    position:absolute; left:0; right:0; bottom:0;
    height:42px; padding:0 16px;
    display:flex; align-items:center; justify-content:space-between;
    border-bottom-left-radius:14px; border-bottom-right-radius:14px;
    text-decoration:none; font-weight:700;
    background:rgba(255,255,255,.92); color:#111;
    border-top:1px solid rgba(0,0,0,.06);
  }
  .more-strip .icon{
    width:24px; height:24px; border-radius:999px;
    border:2px solid currentColor;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:14px; line-height:1;
  }

  /* ====== KPI (tanpa tabel, meniru gambar #2) ====== */
  .kpi-title{ text-align:center; margin:8px 0 24px; font-weight:700; }
  .kpi-grid{
    display:grid; gap:24px;
    grid-template-columns:repeat(auto-fit, minmax(520px, 1fr));
  }
  .kpi-panel{ background:#fff; border:1px solid #dcdcdc; border-radius:4px; box-shadow:0 1px 2px rgba(0,0,0,.02); }
  .kpi-panel h2{ margin:0 0 10px; font-weight:700; }
  .kpi-head, .kpi-row{
    display:grid; grid-template-columns: 80px 1fr 180px;
    align-items:center;
  }
  .kpi-head{
    background:#f1f1f1;
    border-bottom:1px solid #e6e6e6;
    padding:10px 12px;
    font-weight:700;
    border-top-left-radius:4px; border-top-right-radius:4px;
  }
  .kpi-list{ /* body */
    background:#fff;
  }
  .kpi-row{
    padding:9px 12px;
    border-bottom:1px solid #efefef;
    font-size:14px;
  }
  .kpi-row:last-child{ border-bottom:none; }

  /* Full-row highlight Top-3 (warna mirip screenshot) */
  .kpi-row.rank-1{ background:#FFD400 !important; font-weight:700; }
  .kpi-row.rank-2{ background:#C0C0C0 !important; font-weight:600; }
  .kpi-row.rank-3{ background:#CD7F32 !important; color:#fff !important; font-weight:600; }
  .kpi-row.rank-3 > div{ color:#fff !important; }

  /* Kolom */
  .kpi-col-rank{ text-align:left; font-weight:700; }
  .kpi-col-name{ text-transform:uppercase; }
  .kpi-col-val{ text-align:right; font-weight:600; }
  .nama-staff{ min-width:220px; }

  /* Rapikan wrapper card bawaan */
  .card.table-responsive{ border:none; }
</style>
@endpush

@section('content')
<div class="container-fluid">
  <div class="card table-responsive">
    <div class="card-header">
      <h3 class="card-title">
        Selamat Datang, <strong>{{ Auth::user()->id_admin }} / {{ Auth::user()->nama_staff }}</strong>
      </h3>
    </div>

    <div class="card-body" style="min-height:760px">

      {{-- ====== TAB HEADER ====== --}}
      <ul class="nav nav-tabs" id="dashTabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link {{ $currentTab === 'topik_title' ? 'active' : '' }}"
             id="topik-tab" data-toggle="tab" href="#topik" role="tab"
             aria-controls="topik" aria-selected="{{ $currentTab === 'topik_title' ? 'true' : 'false' }}">
            Topik Ranking
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $currentTab === 'kpi' ? 'active' : '' }}"
             id="kpi-tab" data-toggle="tab" href="#kpi" role="tab"
             aria-controls="kpi" aria-selected="{{ $currentTab === 'kpi' ? 'true' : 'false' }}">
            KPI Ranking
          </a>
        </li>
      </ul>

      {{-- ====== TAB CONTENT ====== --}}
      <div class="tab-content p-3" id="dashTabsContent">

        {{-- ================= TAB: TOPIK RANKING ================= --}}
        <div class="tab-pane fade {{ $currentTab === 'topik_title' ? 'show active' : '' }}"
             id="topik" role="tabpanel" aria-labelledby="topik-tab">

          <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <h5 class="mb-2 mb-md-0">Daftar Topik</h5>
          </div>

          @if (empty($topikCards) || collect($topikCards)->isEmpty())
            <div class="alert alert-info mb-0">
              Belum ada data untuk periode aktif.
            </div>
          @else
            <div class="stat-grid">
              @foreach ($topikCards as $c)
                <div class="stat-card"
                     style="background: {{ $c['bg'] }}; color: {{ $c['textColor'] }}; border-color: {{ $c['border'] }};">
                  <div class="count">{{ $c['count'] }}</div>
                  <div class="title">{{ e($c['title']) }}</div>

                  {{-- TOMBOL HANYA TAMPIL JIKA MEMILIKI PRIVILEGE (dari Controller) --}}
                  @if(!empty($canSeeTopicLinks))
                    <a href="{{ $c['moreUrl'] }}" class="more-strip">
                      <span>Info Lebih Lanjut</span><span class="icon">➔</span>
                    </a>
                  @endif
                </div>
              @endforeach
            </div>
          @endif

          @if ($topik instanceof \Illuminate\Pagination\AbstractPaginator)
            <div class="mt-4">
              {{ $topik->appends(['tab' => 'topik_title', 'q' => $search])->onEachSide(1)->links('pagination.topik') }}
            </div>
          @endif
        </div>

        {{-- ================= TAB: KPI (tanpa tabel, mirip gambar #2) ================= --}}
        <div class="tab-pane fade {{ $currentTab === 'kpi' ? 'show active' : '' }}"
             id="kpi" role="tabpanel" aria-labelledby="kpi-tab">

          <h1 class="kpi-title">KPI Ranking HS Group</h1>

          <div class="kpi-grid">
            <section>
              <h2>The Worst</h2>
              <div class="kpi-panel">
                <div class="kpi-head">
                  <div class="kpi-col-rank">Rank</div>
                  <div class="kpi-col-name">Nama Staff</div>
                  <div class="kpi-col-val">Total Kesalahan</div>
                </div>
                <div id="kpi-bad-list" class="kpi-list"></div>
              </div>
            </section>

            <section>
              <h2>The Best</h2>
              <div class="kpi-panel">
                <div class="kpi-head">
                  <div class="kpi-col-rank">Rank</div>
                  <div class="kpi-col-name">Nama Staff</div>
                  <div class="kpi-col-val">Total Tidak Bersalah</div>
                </div>
                <div id="kpi-good-list" class="kpi-list"></div>
              </div>
            </section>
          </div>
        </div>

      </div> {{-- end tab-content --}}
    </div> {{-- end card-body --}}
  </div>
</div>
@endsection

@push('scripts')
<script>
  $(function () {
    const leaderboardUrl = @json($leaderboardUrl);
    const $bad  = $('#kpi-bad-list');
    const $good = $('#kpi-good-list');

    function rowEl(rank, name, value){
      const $row = $('<div class="kpi-row"></div>');
      if (rank === 1) $row.addClass('rank-1');
      else if (rank === 2) $row.addClass('rank-2');
      else if (rank === 3) $row.addClass('rank-3');

      $row.append($('<div class="kpi-col-rank"></div>').text(rank));
      $row.append($('<div class="kpi-col-name"></div>').text((name || '').toUpperCase()));
      $row.append($('<div class="kpi-col-val"></div>').text(value));
      return $row;
    }

    function renderLists(res){
      $bad.empty(); $good.empty();

      (res.bad || []).forEach(function(r, i){
        $bad.append(rowEl(i+1, r.nama_staff, r.total_fault));
      });

      (res.good || []).forEach(function(r, i){
        $good.append(rowEl(i+1, r.nama_staff, r.total_no_fault));
      });
    }

    function fetchLeaderboard(){
      $.get(leaderboardUrl).done(renderLists);
    }

    fetchLeaderboard();
    setInterval(fetchLeaderboard, 60000);

    // buka tab awal sesuai server
    var activeTab = @json($currentTab);
    if (activeTab === 'kpi') { $('#kpi-tab').tab('show'); } else { $('#topik-tab').tab('show'); }
  });
</script>
@endpush
