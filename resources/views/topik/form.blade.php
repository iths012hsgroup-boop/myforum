@extends('layouts.app')

@section('content')
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">Form {{ $item->exists ? 'Edit' : 'Tambah' }} Topik</h5>
  </div>

  <div class="card-body">
    <form method="post" action="{{ $item->exists ? route('topik.update',$item) : route('topik.store') }}">
      @csrf
      @if($item->exists) @method('put') @endif

      <div class="mb-3">
        <label class="form-label">Topik *</label>
        <input id="topikInput" type="text" name="topik_title"
               class="form-control @error('topik_title') is-invalid @enderror"
               value="{{ old('topik_title',$item->topik_title) }}" placeholder="Isi dengan topik">
        @error('topik_title')
          <div class="invalid-feedback">{{ $message }}</div>
        @else
          <div class="invalid-feedback"></div> {{-- placeholder utk JS --}}
        @enderror
      </div>

      <div class="mb-3">
        <label class="form-label">Status *</label>
        <select name="status" class="form-select @error('status') is-invalid @enderror">
          <option value="1" {{ old('status',$item->status ?? 1)==1 ? 'selected' : '' }}>Aktif</option>
          <option value="0" {{ old('status',$item->status ?? 1)==0 ? 'selected' : '' }}>Nonaktif</option>
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="d-flex">
        <button id="btnSimpan" class="btn btn-primary mr-2">Simpan</button>
        <a href="{{ route('topik.index') }}" class="btn btn-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
  const $input = $('#topikInput');
  const $btn   = $('#btnSimpan');
  const $fb    = $input.siblings('.invalid-feedback');
  let timer    = null;

  function setInvalid(msg){
    $input.addClass('is-invalid');
    $fb.text(msg).show();
    $btn.prop('disabled', true);
    if (window.toastr) toastr.error(msg); // optional toast jika pakai toastr
  }

  function setValid(){
    $input.removeClass('is-invalid');
    $fb.text('').hide();
    $btn.prop('disabled', false);
  }

  function checkName(){
    const val = ($input.val()||'').trim();
    if (!val) { setValid(); return; }
    $.get('{{ route('topik.check') }}', {
      topik_title: val,
      ignore_id: '{{ $item->exists ? $item->id : '' }}'
    }).done(function(res){
      if (res && res.exists) setInvalid('Topik ini sudah ada.');
      else setValid();
    }).fail(function(){
      // kalau AJAX gagal, jangan blok user
      setValid();
    });
  }

  $input.on('input', function(){
    clearTimeout(timer);
    timer = setTimeout(checkName, 300);
  });

  $('form').on('submit', function(e){
    if ($input.hasClass('is-invalid')) e.preventDefault();
  });

  // cek awal saat halaman edit
  @if($item->exists)
    checkName();
  @endif
})();
</script>
@endpush
