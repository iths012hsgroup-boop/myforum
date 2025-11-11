@once
  @push('styles')
  <style>
    /* Matikan label pseudo (::before) yg kerap digenerate plugin tabel responsif.
       Pakai wrapper: <div class="table-responsive no-rwd-labels">…</div> */
    .no-rwd-labels :where(td,th)::before{content:none!important;display:none!important;}
    .no-rwd-labels :where(.stacktable, table.stacktable, .st-head-row, .st-key){display:none!important;}
    .no-rwd-labels table{display:table!important;}
  </style>
  @endpush
@endonce

@if ($paginator->hasPages())
@php
  $isFirst   = $paginator->onFirstPage();
  $hasNext   = $paginator->hasMorePages();
  $prevUrl   = $paginator->previousPageUrl();
  $nextUrl   = $paginator->nextPageUrl();
  $firstItem = $paginator->firstItem() ?? 0;
  $lastItem  = $paginator->lastItem() ?? 0;
  $total     = $paginator->total();
@endphp

<nav class="d-flex flex-column align-items-start" aria-label="Pagination">
  <ul class="pagination mb-1">

    {{-- Previous --}}
    <li class="page-item {{ $isFirst ? 'disabled' : '' }}">
      @if($isFirst)
        <span class="page-link" aria-disabled="true" tabindex="-1">Previous</span>
      @else
        <a class="page-link" href="{{ $prevUrl }}" rel="prev" aria-label="Go to previous page">Previous</a>
      @endif
    </li>

    {{-- Numbers --}}
    @foreach ($elements as $element)
      @if (is_string($element))
        <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
      @endif

      @if (is_array($element))
        @foreach ($element as $page => $url)
          <li class="page-item {{ $page === $paginator->currentPage() ? 'active' : '' }}">
            @if ($page === $paginator->currentPage())
              <span class="page-link" aria-current="page">{{ $page }}</span>
            @else
              <a class="page-link" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
            @endif
          </li>
        @endforeach
      @endif
    @endforeach

    {{-- Next --}}
    <li class="page-item {{ $hasNext ? '' : 'disabled' }}">
      @if($hasNext)
        <a class="page-link" href="{{ $nextUrl }}" rel="next" aria-label="Go to next page">Next</a>
      @else
        <span class="page-link" aria-disabled="true" tabindex="-1">Next</span>
      @endif
    </li>

  </ul>

  <div class="small text-muted mt-2">
    Showing <strong>{{ $firstItem }}</strong>
    to <strong>{{ $lastItem }}</strong>
    of <strong>{{ $total }}</strong> results
  </div>
</nav>
@endif
