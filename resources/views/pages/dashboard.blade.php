@extends('layouts.app')
@section('title', 'Dashboard')

@push('styles')
<style>
.stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }

/* Kartu dengan --ratio */
.stat-card{
  --ratio: 0;
  --h: calc((1 - var(--ratio)) * 120); /* 120=green → 0=red */
  --s: 85%;
  --l: 50%;
  background: hsl(var(--h) var(--s) var(--l));
  color:#111;
  border:2px solid transparent;
  border-radius:12px;
  padding:14px;
  transition: transform .12s ease, box-shadow .12s ease;
}
.stat-card.text-light{ color:#fff; }
.stat-card.border-muted{ border-color:#eaeaea; }
.stat-card:hover{ transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }

.stat-card .count{ font:700 28px/1.1 system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial; }
.stat-card .title{ margin-top:4px; font-weight:600; }

/* STRIP: default (teks gelap, panel terang tipis) */
.stat-card .more-strip{
  margin-top:10px;
  display:flex; justify-content:space-between; align-items:center;
  font-weight:600; text-decoration:none;
  color:#111;                              /* ❗ tidak mewarisi dari kartu */
  background: rgba(255,255,255,.55);
  border-radius:10px; padding:8px 10px;
}
.stat-card .more-strip .icon{ transform: translateX(0); transition: transform .12s ease; opacity:.9; }
.stat-card:hover .more-strip .icon{ transform: translateX(2px); }

/* STRIP saat kartu gelap (ratio tinggi) → panel sedikit lebih gelap agar kontras */
.stat-card.text-light .more-strip{
  background: rgba(255,255,255,.22);
  color:#111;                              /* tetap gelap sesuai keinginan */
}
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
              @php
                // pastikan ada nilai aman
                $count = (int) ($c['count'] ?? 0);
                $ratio = (float) ($c['ratio'] ?? 0); // 0..1, dihitung di controller
                // threshold kontras teks: bebas atur (0.55/0.6 cocok)
                $useLightText = $ratio >= 0.55;
              @endphp

              <div class="stat-card {{ $count===0 ? 'border-muted' : '' }} {{ $useLightText ? 'text-light' : '' }}"
                   style="--ratio: {{ number_format($ratio, 6, '.', '') }}">
                <div class="count">{{ $count }}</div>
                <div class="title">{{ e($c['title'] ?? '-') }}</div>

                @if(!empty($canSeeTopicLinks))
                  <a href="{{ $c['moreUrl'] ?? '#' }}" class="more-strip">
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

        {{-- ================= TAB: KPI (tanpa tabel) ================= --}}
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
    // === KPI AJAX (tetap sama) ===
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
      (res.bad || []).forEach((r, i)=> $bad.append(rowEl(i+1, r.nama_staff, r.total_fault)));
      (res.good|| []).forEach((r, i)=> $good.append(rowEl(i+1, r.nama_staff, r.total_no_fault)));
    }

    function fetchLeaderboard(){ $.get(leaderboardUrl).done(renderLists); }
    fetchLeaderboard();
    setInterval(fetchLeaderboard, 60000);

    var activeTab = @json($currentTab);
    if (activeTab === 'kpi') { $('#kpi-tab').tab('show'); } else { $('#topik-tab').tab('show'); }
  });
</script>
@endpush
