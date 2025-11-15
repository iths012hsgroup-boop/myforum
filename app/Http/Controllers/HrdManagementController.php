<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AbsensiReportExport;
use App\Helpers\AuthLink;
use App\Models\User;
use App\Models\Daftarsitus;
use App\Models\Absensi;
use App\Models\AbsensiReport;
use Carbon\Carbon;

class HrdManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* -----------------------------------------------------------------
     | Helpers umum
     * ----------------------------------------------------------------*/

    /**
     * Jika user tak punya akses HRD → return view('error'), kalau punya → null.
     */
    private function ensureHrdAccess()
    {
        $user  = Auth::user();
        $akses = AuthLink::access_url($user->id_admin, 'hrdmanagement');
        if (empty($akses) || $akses[0]->nilai == 0) {
            return view('error');
        }
        return null;
    }

    /**
     * Tentukan scope situs user: [namaSitusLogin, idSitusArray, isAllSite, user]
     */
    private function resolveUserSiteScope(): array
    {
        $user           = Auth::user();
        $namaSitusLogin = 'Situs Tidak Ditemukan';
        $idSitusArray   = [];
        $isAllSite      = false;

        if ($user && $user->id_situs) {
            $idSitusArray = array_map('trim', explode(',', $user->id_situs));

            if (in_array('1', $idSitusArray, true)) {
                $namaSitusLogin = 'ALL SITE';
                $isAllSite      = true;
            } else {
                $idSitusPertama = $idSitusArray[0] ?? null;
                if ($idSitusPertama) {
                    $situs = Daftarsitus::find($idSitusPertama);
                    if ($situs) $namaSitusLogin = $situs->nama_situs;
                }
            }
        }

        return [$namaSitusLogin, $idSitusArray, $isAllSite, $user];
    }

    /**
     * Ambil user aktif yang terhubung ke site (mendukung multi-site di kolom id_situs).
     */
    private function getActiveUsersBySite(array $idSitusArray, bool $isAllSite)
    {
        $q = User::where('status', 1)->select('id', 'id_admin', 'nama_staff', 'id_situs');

        if (!$isAllSite && !empty($idSitusArray)) {
            $q->where(function ($query) use ($idSitusArray) {
                foreach ($idSitusArray as $id) {
                    $query->orWhereRaw('FIND_IN_SET(?, id_situs)', [trim($id)]);
                }
            });
        }

        return $q->get();
    }

    /**
     * Normalisasi list id: "1,2,3" | ["1","2"] → ["1","2","3"]
     */
    private function normalizeIdList($raw): array
    {
        if (is_array($raw)) return array_filter($raw);
        if (!empty($raw))   return array_filter(array_map('trim', explode(',', $raw)));
        return [];
    }

    /**
     * Parse "YYYY-1|2" → [ok(bool), tahun(int), periodeKe(1/2), start(string), end(string)]
     */
    private function parsePeriodeString(string $periode): array
    {
        $parts = explode('-', $periode);
        if (count($parts) !== 2) return [false, 0, 0, null, null];

        $tahun     = (int) $parts[0];
        $periodeKe = (int) $parts[1];

        if ($periodeKe === 1) {
            return [true, $tahun, 1, Carbon::create($tahun, 1, 1)->toDateString(), Carbon::create($tahun, 6, 30)->toDateString()];
        }
        if ($periodeKe === 2) {
            return [true, $tahun, 2, Carbon::create($tahun, 7, 1)->toDateString(), Carbon::create($tahun, 12, 31)->toDateString()];
        }
        return [false, $tahun, $periodeKe, null, null];
    }

    /**
     * Gabungkan status dari request + flag cuti/range.
     * return [statusString, isCuti, hasRange, statusArray]
     */
    private function buildStatusFromRequest(Request $request): array
    {
        $statusArray  = $request->input('kehadiran', []);
        $statusString = !empty($statusArray) ? implode(', ', $statusArray) : 'HADIR';

        $isCuti   = in_array('CUTI', $statusArray);
        $hasRange = $request->filled('cuti_start') && $request->filled('cuti_end');

        return [$statusString, $isCuti, $hasRange, $statusArray];
    }

    /**
     * Helper respons untuk Ajax vs non-Ajax (redirect back).
     */
    private function respondOk(Request $request, string $message)
    {
        return $request->ajax()
            ? response()->json(['success' => true, 'message' => $message])
            : redirect()->back()->with('success', $message);
    }

    /* -----------------------------------------------------------------
     | INDEX & DATA STAFF
     * ----------------------------------------------------------------*/

    public function index(Request $request, Builder $builder)
    {
        if ($resp = $this->ensureHrdAccess()) return $resp;

        [$namaSitusLogin, $idSitusArray, $isAllSite, $userLogin] = $this->resolveUserSiteScope();

        $filteredUsers = ($userLogin && !empty($idSitusArray))
            ? $this->getActiveUsersBySite($idSitusArray, $isAllSite)
            : collect();

        // === INI BAGIAN BARU: definisi DataTable untuk staffTable ===
        $html = $builder
            ->setTableId('staffTable') // id <table> di HTML
            ->columns([
                [
                    'data'       => 'DT_RowIndex',
                    'name'       => null,
                    'title'      => 'No.',
                    'orderable'  => false,
                    'searchable' => false,
                    'class'      => 'text-center',
                ],
                [
                    'data'  => 'id_admin',
                    'name'  => 'id_admin',
                    'title' => 'ID Admin',
                ],
                [
                    'data'  => 'nama_staff',
                    'name'  => 'nama_staff',
                    'title' => 'Nama Staff',
                ],
                [
                    'data'       => 'action',
                    'name'       => 'action',
                    'title'      => 'Action',
                    'orderable'  => false,
                    'searchable' => false,
                    'class'      => 'text-center',
                ],
            ])
            ->minifiedAjax(route('hrdmanagement.staff.data')) // route JSON
            ->parameters([
                'processing'  => true,
                'serverSide'  => true,
                'responsive'  => true,
                'lengthChange'=> true,
                'searching'   => true,
                'ordering'    => true,
                'info'        => true,
                'autoWidth'   => false,
                'language'    => [
                    'lengthMenu'   => 'Tampilkan _MENU_ data per halaman',
                    'zeroRecords'  => 'Tidak ada data yang ditemukan',
                    'info'         => 'Menampilkan halaman _PAGE_ dari _PAGES_',
                    'infoEmpty'    => 'Tidak ada data',
                    'infoFiltered' => '(difilter dari total _MAX_ data)',
                    'search'       => 'Cari:',
                    'paginate'     => [
                        'first'    => 'Awal',
                        'last'     => 'Akhir',
                        'next'     => 'Berikutnya',
                        'previous' => 'Sebelumnya',
                    ],
                ],
            ]);

        return view('pages.hrdmanagement.index', compact('namaSitusLogin', 'filteredUsers', 'html'));
    }


    public function staffData()
    {
        [, $idSitusArray, $isAllSite, $userLogin] = $this->resolveUserSiteScope();

        $users = ($userLogin && !empty($idSitusArray))
            ? $this->getActiveUsersBySite($idSitusArray, $isAllSite)
            : collect();

        return DataTables::of($users)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $idSitusAll = $row->id_situs;

                return '
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button"
                            class="btn btn-primary btn-sm absensi-baru-btn"
                            data-id-admin="'.$row->id_admin.'"
                            data-nama-staff="'.$row->nama_staff.'"
                            data-id-situs-user="'.$idSitusAll.'"
                            title="Absensi Baru Hari Ini">
                            <i class="fas fa-plus"></i> Absensi
                        </button>

                        <button type="button"
                            class="btn btn-warning btn-sm edit-absensi-btn"
                            data-id-admin="'.$row->id_admin.'"
                            data-nama-staff="'.$row->nama_staff.'"
                            data-id-situs-user="'.$idSitusAll.'"
                            title="Edit Absensi Hari Ini">
                            <i class="fas fa-pencil-alt"></i> Edit
                        </button>

                        <button type="button"
                            class="btn btn-info btn-sm detail-absensi-btn"
                            data-id-admin="'.$row->id_admin.'"
                            data-nama-staff="'.$row->nama_staff.'"
                            data-id-situs-user="'.$idSitusAll.'"
                            title="Lihat Riwayat Absensi">
                            <i class="fas fa-list"></i> Details
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /* -----------------------------------------------------------------
     | CRUD ABSENSI (FORM HRD)
     * ----------------------------------------------------------------*/

    public function updateAbsensi(Request $request, $id)
    {
        $request->validate([
            'id_admin'   => 'required',
            'id_situs'   => 'required|string|max:50',
            'nama_staff' => 'required|string|max:100',
            'tanggal'    => 'required|date',
            'kehadiran'  => 'required|array',
            'remarks'    => 'required|string|max:255',
            'cuti_start' => 'nullable|date',
            'cuti_end'   => 'nullable|date|after_or_equal:cuti_start',
        ]);

        [$statusString, $isCuti, $hasRange] = $this->buildStatusFromRequest($request);

        $absensi  = Absensi::findOrFail($id);
        $editedBy = Auth::user()->id_admin ?? Auth::id();

        $absensi->update([
            'nama_staff' => $request->nama_staff,
            'id_situs'   => $request->id_situs,
            'status'     => $statusString,
            'remarks'    => $request->remarks,
            'edited_by'  => $editedBy,
            'tanggal'    => $request->tanggal,
            'cuti_start' => $isCuti && $hasRange ? $request->cuti_start : null,
            'cuti_end'   => $isCuti && $hasRange ? $request->cuti_end   : null,
        ]);

        return $this->respondOk($request, 'Data absensi berhasil diupdate.');
    }

    public function storeAbsensi(Request $request)
    {
        $request->validate([
            'id_admin'   => 'required|string|max:50',
            'id_situs'   => 'required|string|max:50',
            'nama_staff' => 'required|string|max:100',
            'tanggal'    => 'required|date',
            'remarks'    => 'required|string',
            'kehadiran'  => 'required|array',
            'cuti_start' => 'nullable|date',
            'cuti_end'   => 'nullable|date|after_or_equal:cuti_start',
        ]);

        [$statusString, $isCuti, $hasRange] = $this->buildStatusFromRequest($request);
        $userCode = Auth::user()->id_admin ?? Auth::id();

        Absensi::create([
            'id_admin'   => $request->id_admin,
            'id_situs'   => $request->id_situs,
            'nama_staff' => $request->nama_staff,
            'status'     => $statusString,
            'remarks'    => $request->remarks,
            'created_by' => $userCode,
            'edited_by'  => null,
            'tanggal'    => $request->tanggal,
            'cuti_start' => $isCuti && $hasRange ? $request->cuti_start : null,
            'cuti_end'   => $isCuti && $hasRange ? $request->cuti_end   : null,
        ]);

        return $this->respondOk($request, 'Absensi berhasil disimpan!');
    }

    public function listAbsensi(Request $request)
    {
        $request->validate(['id_admin' => 'required']);

        $today    = Carbon::today();
        $lastWeek = $today->copy()->subDays(6);

        $rows = Absensi::from('tbhs_absensi as a')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, a.id_situs)'), '>', DB::raw('0'));
            })
            ->where('a.id_admin', $request->id_admin)
            ->where('a.soft_delete', 0)
            ->when($request->filled('id_situs'), fn($q) => $q->where('a.id_situs', 'LIKE', '%' . $request->id_situs . '%'))
            ->whereBetween('a.tanggal', [$lastWeek, $today])
            ->orderBy('a.tanggal', 'desc')
            ->orderBy('a.id', 'desc')
            ->groupBy('a.id','a.id_admin','a.tanggal','a.status','a.remarks','a.id_situs','a.cuti_start','a.cuti_end')
            ->selectRaw('
                a.id, a.id_admin, a.tanggal, a.status, a.remarks, a.id_situs, a.cuti_start, a.cuti_end,
                GROUP_CONCAT(DISTINCT s.nama_situs ORDER BY s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->get();

        return response()->json($rows);
    }

    public function destroyAbsensi($id)
    {
        $absensi = Absensi::find($id);
        if (!$absensi) {
            return response()->json(['success' => false, 'message' => 'Data absensi tidak ditemukan.'], 404);
        }

        $absensi->soft_delete = 1;
        $absensi->save();

        return response()->json(['success' => true, 'message' => 'Data absensi berhasil dihapus.']);
    }

    public function checkAbsensiDuplicate(Request $request)
    {
        $request->validate([
            'id_admin' => 'required',
            'tanggal'  => 'required|date',
        ]);

        $exists = Absensi::where('id_admin', $request->id_admin)
            ->whereDate('tanggal', $request->tanggal)
            ->where('soft_delete', 0)
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    public function detailAbsensi(Request $request)
    {
        $request->validate(['id_admin' => 'required']);

        $rows = Absensi::from('tbhs_absensi as a')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, a.id_situs)'), '>', DB::raw('0'));
            })
            ->where('a.id_admin', $request->id_admin)
            ->where('a.soft_delete', 0)
            ->orderBy('a.tanggal', 'desc')
            ->orderBy('a.id', 'desc')
            ->groupBy('a.id','a.id_admin','a.tanggal','a.status','a.remarks','a.id_situs','a.cuti_start','a.cuti_end')
            ->selectRaw('
                a.id, a.id_admin, a.tanggal, a.status, a.remarks, a.id_situs, a.cuti_start, a.cuti_end,
                GROUP_CONCAT(DISTINCT s.nama_situs ORDER BY s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->get();

        return response()->json($rows);
    }
}
