<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopikRequest;
use App\Models\Topik;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Helpers\AuthLink;

class TopikController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Cek akses menu Topik lewat tbhs_privilege_access + tbhs_menu.
     * Akan mengizinkan jika user punya salah satu dari menu_link berikut:
     * - dashboard/topik
     * - topik
     * - dashboard (fallback)
     */
    protected function hasTopikPrivilege(Request $r): bool
    {
        $id = auth()->user()->id_admin;

        foreach (['dashboard/topik', 'topik', 'dashboard'] as $link) {
            $akses = AuthLink::access_url($id, $link);
            if (!empty($akses) && (int)($akses[0]->nilai ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }

    /** Jika tak punya akses: kembalikan view error, selain itu return null */
    protected function guardTopik(Request $r): ?View
    {
        if (!$this->hasTopikPrivilege($r)) {
            return view('error');
        }
        return null;
    }

    public function index(Request $r): View
    {
        if ($denied = $this->guardTopik($r)) { return $denied; }

        $search  = $r->query('q');
        $perPage = (int) $r->input('per_page', 10);

        // Query hanya di controller (hide yang soft_delete = 1)
        $topik = Topik::search($search)
            ->where('soft_delete', 0)
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Siapkan data siap pakai untuk Blade (nomor, label, URL aksi)
        $startNo = ($topik->currentPage() - 1) * $topik->perPage();
        $rows = collect($topik->items())->map(function ($row, $i) {
            $status = (int) ($row->status ?? 0);
            return [
                'no'          => null, // diisi di bawah
                'id'          => $row->id,
                'topik_title' => $row->topik_title,
                'status'      => $status,
                'statusLabel' => $status ? 'Aktif' : 'Nonaktif',
                'statusClass' => $status ? 'btn-success' : 'btn-secondary',
                'toggleUrl'   => route('topik.toggle',  $row),
                'editUrl'     => route('topik.edit',    $row),
                'destroyUrl'  => route('topik.destroy', $row),
            ];
        })->values();

        // Isi nomor urut berdasarkan halaman
        foreach ($rows as $idx => &$rrow) {
            $rrow['no'] = $startNo + $idx + 1;
        }
        unset($rrow);

        // URL untuk form create (modal)
        $storeUrl = route('topik.store');

        return view('topik.index', [
            'topik'    => $topik,     // paginator untuk ->links()
            'search'   => $search,
            'rows'     => $rows,      // data tabel siap render
            'storeUrl' => $storeUrl,  // action form create
        ]);
    }

    public function create(Request $r): View
    {
        if ($denied = $this->guardTopik($r)) { return $denied; }

        return view('topik.form', ['item' => new Topik()]);
    }

    public function store(TopikRequest $r): RedirectResponse
    {
        if ($denied = $this->guardTopik($r)) {
            return redirect()->route('topik.index');
        }

        // Fallback input lama "topik" -> "topik_title"
        if ($r->has('topik') && !$r->has('topik_title')) {
            $r->merge(['topik_title' => $r->input('topik')]);
        }

        // Validasi unik topik_title (abaikan yang soft_delete = 1)
        $table = (new Topik)->getTable();
        $r->validate([
            'topik_title' => [
                'required', 'string', 'max:255',
                Rule::unique($table, 'topik_title')->where(fn($q) => $q->where('soft_delete', 0)),
            ],
            'status' => ['nullable', 'in:0,1'],
        ], [
            'topik_title.unique' => 'Topik ini sudah ada.',
        ]);

        Topik::create($r->validated());

        return redirect()->route('topik.index')->with('ok', 'Topik berhasil ditambahkan.');
    }

    public function edit(Request $r, Topik $topik): View
    {
        if ($denied = $this->guardTopik($r)) { return $denied; }

        return view('topik.form', ['item' => $topik]);
    }

    public function update(TopikRequest $r, Topik $topik): RedirectResponse
    {
        if ($denied = $this->guardTopik($r)) {
            return redirect()->route('topik.index');
        }

        // Fallback input lama "topik" -> "topik_title"
        if ($r->has('topik') && !$r->has('topik_title')) {
            $r->merge(['topik_title' => $r->input('topik')]);
        }

        $table = (new Topik)->getTable();
        $r->validate([
            'topik_title' => [
                'required', 'string', 'max:255',
                Rule::unique($table, 'topik_title')
                    ->ignore($topik->id)
                    ->where(fn($q) => $q->where('soft_delete', 0)),
            ],
            'status' => ['nullable', 'in:0,1'],
        ], [
            'topik_title.unique' => 'Topik ini sudah ada.',
        ]);

        $topik->update($r->validated());

        return redirect()->route('topik.index')->with('ok', 'Topik berhasil diperbarui.');
    }

    public function destroy(Request $r, Topik $topik): RedirectResponse
    {
        if ($denied = $this->guardTopik($r)) {
            return redirect()->route('topik.index');
        }

        $topik->update(['soft_delete' => 1]);

        return redirect()->route('topik.index')->with('ok', 'Topik dihapus (soft).');
    }

    public function toggleStatus(Request $r, Topik $topik): RedirectResponse
    {
        if ($denied = $this->guardTopik($r)) {
            return redirect()->route('topik.index');
        }

        $topik->update(['status' => $topik->status ? 0 : 1]);

        return back()->with('ok', 'Status diubah.');
    }

    /** AJAX: cek duplikasi nama topik */
    public function check(Request $r)
    {
        if (!$this->hasTopikPrivilege($r)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        // Terima 'topik_title' (baru) atau 'topik' (legacy)
        $name     = trim($r->get('topik_title', $r->get('topik', '')));
        $ignoreId = $r->get('ignore_id');

        if ($name === '') {
            return response()->json(['exists' => false]);
        }

        $q = Topik::where('topik_title', $name)->where('soft_delete', 0);
        if ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        }

        return response()->json(['exists' => $q->exists()]);
    }
}
