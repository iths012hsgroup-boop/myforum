<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    /* ==========================
     * HELPER UMUM
     * ========================== */

    private function ensureHrdAccess()
    {
        $user  = Auth::user();
        $akses = AuthLink::access_url($user->id_admin, 'hrdmanagement');

        if (empty($akses) || $akses[0]->nilai == 0) {
            return view('error');
        }

        return null;
    }

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
                    if ($situs) {
                        $namaSitusLogin = $situs->nama_situs;
                    }
                }
            }
        }

        return [$namaSitusLogin, $idSitusArray, $isAllSite, $user];
    }

    private function applySiteFilter($query, array $idSitusArray, bool $isAllSite, string $column = 'id_situs')
    {
        return $query->when(!$isAllSite && !empty($idSitusArray), function ($q) use ($idSitusArray, $column) {
            $q->whereIn($column, $idSitusArray);
        });
    }

    private function getActiveUsersBySite(array $idSitusArray, bool $isAllSite)
    {
        $q = User::where('status', 1)
            ->select('id', 'id_admin', 'nama_staff', 'id_situs');

        if (!$isAllSite && !empty($idSitusArray)) {
            $q->where(function ($query) use ($idSitusArray) {
                foreach ($idSitusArray as $idSitus) {
                    $query->orWhereRaw('FIND_IN_SET(?, id_situs)', [trim($idSitus)]);
                }
            });
        }

        return $q->get();
    }

    private function monthNames(): array
    {
        return [
            null,
            'Januari','Februari','Maret','April','Mei','Juni',
            'Juli','Agustus','September','Oktober','November','Desember',
        ];
    }

    private function normalizeIdList($raw): array
    {
        if (is_array($raw)) {
            return array_filter($raw);
        }
        if (!empty($raw)) {
            return array_filter(array_map('trim', explode(',', $raw)));
        }
        return [];
    }

    /**
     * Helper selectRaw untuk agregat status (telat, sakit, izin, tanpa_kabar, cuti)
     */
    private function statusSumSelectRaw(string $alias = ''): string
    {
        $p = $alias ? $alias . '.' : '';

        return "
            SUM(CASE WHEN {$p}status LIKE '%TELAT%'       THEN 1 ELSE 0 END) as telat,
            SUM(CASE WHEN {$p}status LIKE '%SAKIT%'       THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN {$p}status LIKE '%IZIN%'        THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN {$p}status LIKE '%TANPA KABAR%' THEN 1 ELSE 0 END) as tanpa_kabar,
            SUM(CASE WHEN {$p}status LIKE '%CUTI%'        THEN 1 ELSE 0 END) as cuti
        ";
    }

    /**
     * Helper parse "YYYY-1/2" -> [tahun, periodeKe, startDate, endDate]
     */
    private function parsePeriodeString(string $periode): array
    {
        $parts = explode('-', $periode);

        if (count($parts) !== 2) {
            return [false, 0, 0, null, null];
        }

        $tahun     = (int) $parts[0];
        $periodeKe = (int) $parts[1];

        if ($periodeKe === 1) {
            $start = Carbon::create($tahun, 1, 1)->toDateString();
            $end   = Carbon::create($tahun, 6, 30)->toDateString();
        } elseif ($periodeKe === 2) {
            $start = Carbon::create($tahun, 7, 1)->toDateString();
            $end   = Carbon::create($tahun, 12, 31)->toDateString();
        } else {
            return [false, $tahun, $periodeKe, null, null];
        }

        return [true, $tahun, $periodeKe, $start, $end];
    }

    /**
     * Helper bikin string status + flag cuti
     */
    private function buildStatusFromRequest(Request $request): array
    {
        $statusArray  = $request->input('kehadiran', []);
        $statusString = !empty($statusArray) ? implode(', ', $statusArray) : 'HADIR';

        $isCuti   = in_array('CUTI', $statusArray);
        $hasRange = $request->filled('cuti_start') && $request->filled('cuti_end');

        return [$statusString, $isCuti, $hasRange, $statusArray];
    }

    /* ==========================
     * INDEX & DATA STAFF
     * ========================== */

    public function index(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) {
            return $resp;
        }

        [$namaSitusLogin, $idSitusArray, $isAllSite, $userLogin] = $this->resolveUserSiteScope();

        $filteredUsers = ($userLogin && !empty($idSitusArray))
            ? $this->getActiveUsersBySite($idSitusArray, $isAllSite)
            : collect();

        return view('pages.hrdmanagement.index', compact('namaSitusLogin', 'filteredUsers'));
    }

    public function staffData()
    {
        [$namaSitusLogin, $idSitusArray, $isAllSite, $userLogin] = $this->resolveUserSiteScope();

        $rows = [];

        if ($userLogin && !empty($idSitusArray)) {
            $filteredUsers = $this->getActiveUsersBySite($idSitusArray, $isAllSite);

            foreach ($filteredUsers as $user) {
                $idSitusPertama = null;
                if (!empty($user->id_situs)) {
                    $parts          = explode(',', $user->id_situs);
                    $idSitusPertama = trim($parts[0]);
                }

                $rows[] = [
                    'id_admin'       => $user->id_admin,
                    'nama_staff'     => $user->nama_staff,
                    'id_situs_first' => $idSitusPertama,
                    'id_situs'       => $user->id_situs,
                ];
            }
        }

        return response()->json(['data' => $rows]);
    }

    /* ==========================
     * CRUD ABSENSI (FORM HRD)
     * ========================== */

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

        [$statusString, $isCuti, $hasRange, $statusArray] = $this->buildStatusFromRequest($request);

        $absensi  = Absensi::findOrFail($id);
        $editedBy = Auth::user()->id_admin ?? Auth::id();

        $data = [
            'nama_staff' => $request->nama_staff,
            'id_situs'   => $request->id_situs,
            'status'     => $statusString,
            'remarks'    => $request->remarks,
            'edited_by'  => $editedBy,
            'tanggal'    => $request->tanggal,
            'cuti_start' => $isCuti && $hasRange ? $request->cuti_start : null,
            'cuti_end'   => $isCuti && $hasRange ? $request->cuti_end   : null,
        ];

        $absensi->update($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data absensi berhasil diupdate.',
            ]);
        }

        return redirect()->back()->with('success', 'Data absensi berhasil diupdate.');
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

        $data = [
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
        ];

        Absensi::create($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Absensi berhasil disimpan!',
            ]);
        }

        return redirect()->back()->with('success', 'Absensi berhasil disimpan!');
    }

    public function listAbsensi(Request $request)
    {
        $request->validate([
            'id_admin' => 'required',
        ]);

        $today    = Carbon::today();
        $lastWeek = $today->copy()->subDays(6);

        $absensi = Absensi::from('tbhs_absensi as a')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, a.id_situs)'), '>', DB::raw('0'));
            })
            ->where('a.id_admin', $request->id_admin)
            ->where('a.soft_delete', 0)
            ->when($request->filled('id_situs'), function ($q) use ($request) {
                $q->where('a.id_situs', 'LIKE', '%' . $request->id_situs . '%');
            })
            ->whereBetween('a.tanggal', [$lastWeek, $today])
            ->orderBy('a.tanggal', 'desc')
            ->orderBy('a.id', 'desc')
            ->groupBy(
                'a.id',
                'a.id_admin',
                'a.tanggal',
                'a.status',
                'a.remarks',
                'a.id_situs',
                'a.cuti_start',
                'a.cuti_end'
            )
            ->selectRaw('
                a.id,
                a.id_admin,
                a.tanggal,
                a.status,
                a.remarks,
                a.id_situs,
                a.cuti_start,
                a.cuti_end,
                GROUP_CONCAT(DISTINCT s.nama_situs ORDER BY s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->get();

        return response()->json($absensi);
    }

    public function destroyAbsensi($id)
    {
        $absensi = Absensi::find($id);

        if (!$absensi) {
            return response()->json([
                'success' => false,
                'message' => 'Data absensi tidak ditemukan.',
            ], 404);
        }

        $absensi->soft_delete = 1;
        $absensi->save();

        return response()->json([
            'success' => true,
            'message' => 'Data absensi berhasil dihapus.',
        ]);
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

        return response()->json([
            'exists' => $exists,
        ]);
    }

    public function detailAbsensi(Request $request)
    {
        $request->validate([
            'id_admin' => 'required',
        ]);

        $absensi = Absensi::from('tbhs_absensi as a')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, a.id_situs)'), '>', DB::raw('0'));
            })
            ->where('a.id_admin', $request->id_admin)
            ->where('a.soft_delete', 0)
            ->orderBy('a.tanggal', 'desc')
            ->orderBy('a.id', 'desc')
            ->groupBy(
                'a.id',
                'a.id_admin',
                'a.tanggal',
                'a.status',
                'a.remarks',
                'a.id_situs',
                'a.cuti_start',
                'a.cuti_end'
            )
            ->selectRaw('
                a.id,
                a.id_admin,
                a.tanggal,
                a.status,
                a.remarks,
                a.id_situs,
                a.cuti_start,
                a.cuti_end,
                GROUP_CONCAT(DISTINCT s.nama_situs ORDER BY s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->get();

        return response()->json($absensi);
    }

    /* ==========================
     * REPORTING ABSENSI
     * ========================== */

    public function reportingAbsensi()
    {
        if ($resp = $this->ensureHrdAccess()) {
            return $resp;
        }

        $sites = Daftarsitus::select('id', 'nama_situs')
            ->orderBy('nama_situs')
            ->get();

        return view('pages.hrdmanagement.reportingabsensi', compact('sites'));
    }

    public function getReportingAbsensiData(Request $request)
    {
        $ids     = $this->normalizeIdList($request->input('id_situs'));
        $periode = $request->input('periode');
        $tahun   = $request->input('tahun');

        $rows = DB::table('tbhs_absensireport as r')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, r.id_situs)'), '>', DB::raw('0'));
            })
            ->when($ids, function ($q) use ($ids) {
                $q->where(function ($sub) use ($ids) {
                    foreach ($ids as $id) {
                        $sub->orWhereRaw('FIND_IN_SET(?, r.id_situs)', [$id]);
                    }
                });
            })
            ->when($periode, function ($q) use ($periode) {
                $q->where('r.periode', $periode);
            })
            ->when(!$periode && $tahun, function ($q) use ($tahun) {
                $q->where('r.periode', 'like', $tahun . '-%');
            })
            ->groupBy(
                'r.id',
                'r.id_admin',
                'r.nama_staff',
                'r.id_situs',
                'r.periode',
                'r.sakit',
                'r.izin',
                'r.telat',
                'r.tanpa_kabar',
                'r.cuti',
                'r.total_absensi'
            )
            ->selectRaw("
                r.id,
                r.id_admin,
                r.nama_staff,
                r.id_situs,
                GROUP_CONCAT(DISTINCT s.nama_situs ORDER BY s.nama_situs SEPARATOR ', ') AS nama_situs,
                r.periode,
                r.sakit,
                r.izin,
                r.telat,
                r.tanpa_kabar,
                r.cuti,
                r.total_absensi
            ")
            ->orderByDesc('r.periode')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function generateAbsensiReport(Request $request)
    {
        $request->validate([
            'periode' => 'required|string',
        ]);

        $periode = $request->input('periode');
        [$ok, $tahun, $periodeKe, $start, $end] = $this->parsePeriodeString($periode);

        if (!$ok) {
            return response()->json([
                'success' => false,
                'message' => 'Format periode tidak valid. Gunakan format: TAHUN-PERIODE, contoh: 2025-1',
            ], 422);
        }

        $rows = DB::table('tbhs_absensi as a')
            ->select(
                'a.id_admin',
                'a.nama_staff',
                'a.id_situs',
                DB::raw("'{$periode}' as periode"),
                DB::raw("SUM(CASE WHEN a.status LIKE '%SAKIT%' THEN 1 ELSE 0 END)       as sakit"),
                DB::raw("SUM(CASE WHEN a.status LIKE '%IZIN%' THEN 1 ELSE 0 END)       as izin"),
                DB::raw("SUM(CASE WHEN a.status LIKE '%TELAT%' THEN 1 ELSE 0 END)      as telat"),
                DB::raw("SUM(CASE WHEN a.status LIKE '%TANPA KABAR%' THEN 1 ELSE 0 END) as tanpa_kabar"),
                DB::raw("SUM(CASE WHEN a.status LIKE '%CUTI%' THEN 1 ELSE 0 END)       as cuti"),
                DB::raw("COUNT(*) as total_absensi")
            )
            ->whereBetween('a.tanggal', [$start, $end])
            ->where('a.soft_delete', 0)
            ->groupBy('a.id_admin', 'a.nama_staff', 'a.id_situs')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data absensi untuk periode ini.',
            ]);
        }

        foreach ($rows as $row) {
            AbsensiReport::updateOrCreate(
                [
                    'id_admin' => $row->id_admin,
                    'id_situs' => $row->id_situs,
                    'periode'  => $row->periode,
                ],
                [
                    'nama_staff'    => $row->nama_staff,
                    'sakit'         => (int) $row->sakit,
                    'izin'          => (int) $row->izin,
                    'telat'         => (int) $row->telat,
                    'tanpa_kabar'   => (int) $row->tanpa_kabar,
                    'cuti'          => (int) $row->cuti,
                    'total_absensi' => (int) $row->total_absensi,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Report semua karyawan berhasil digenerate.',
            'total'   => $rows->count(),
        ]);
    }

    public function exportAbsensiReport(Request $request)
    {
        $request->validate([
            'tahun'      => 'required|integer',
            'periode_ke' => 'nullable|in:1,2',
            'id_situs'   => 'nullable|string',
        ]);

        $tahun       = (string) $request->query('tahun');
        $periodeKe   = $request->query('periode_ke');
        $idSitusList = $this->normalizeIdList($request->query('id_situs'));
        $periode     = $periodeKe ? ($tahun . '-' . $periodeKe) : null;

        $suffixPeriode = $periodeKe ? ('_periode_' . $periodeKe) : '_all_periode';
        $suffixSitus   = $idSitusList ? ('_situs_' . implode('_', $idSitusList)) : '_all_situs';

        $fileName = 'report_absensi_' . $tahun . $suffixPeriode . $suffixSitus . '.xlsx';

        return Excel::download(
            new AbsensiReportExport($tahun, $periode, $idSitusList ?: null),
            $fileName
        );
    }

    /* ==========================
     * GRAFIK (TAB GRAFIK & COMPARE)
     * ========================== */

    public function grafik(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) {
            return $resp;
        }

        [$namaSitusLogin, $idSitusArray, $isAllSite, $userLogin] = $this->resolveUserSiteScope();

        $now                = Carbon::now();
        $year               = $now->year;
        $currentMonth       = $now->month;
        $currentMonthNumber = $currentMonth;
        $allMonths          = $this->monthNames();

        $yearQuery = Absensi::selectRaw('DISTINCT YEAR(tanggal) as tahun')
            ->where('soft_delete', 0);

        $yearQuery = $this->applySiteFilter($yearQuery, $idSitusArray, $isAllSite, 'id_situs');

        $compareYears = $yearQuery
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        $compareYear = (int) $request->query('compare_year', $year);

        $mainData = $this->buildMainGrafikData(
            $request,
            $year,
            $currentMonth,
            $allMonths,
            $idSitusArray,
            $isAllSite
        );

        $compareData = $this->buildPerbandinganData(
            $compareYear,
            $allMonths,
            $idSitusArray,
            $isAllSite
        );

        return view('pages.hrdmanagement.grafik', array_merge(
            [
                'namaSitusLogin'     => $namaSitusLogin,
                'currentMonthNumber' => $currentMonthNumber,
                'year'               => $year,
                'compareYears'       => $compareYears,
                'compareYear'        => $compareYear,
            ],
            $mainData,
            $compareData
        ));
    }

    private function buildMainGrafikData(
        Request $request,
        int $year,
        int $currentMonth,
        array $allMonths,
        array $idSitusArray,
        bool $isAllSite
    ): array {
        $defaultPeriode = ($currentMonth <= 6) ? 1 : 2;
        $periodeKe      = (int) $request->query('periode', $defaultPeriode);

        if ($periodeKe === 1) {
            $startMonth = 1;
            $endMonth   = 6;
        } else {
            $periodeKe  = 2;
            $startMonth = 7;
            $endMonth   = 12;
        }

        [
            $labels,
            $bulanNumbers,
            $dataTelat,
            $dataSakit,
            $dataIzin,
            $dataTanpaKabar,
            $dataCuti,
        ] = $this->getPeriodeBarAggregates($year, $startMonth, $endMonth, $allMonths, $idSitusArray, $isAllSite);

        $bulanSekarangLabel = $allMonths[$currentMonth];

        // Aggregat bulan berjalan
        $bulanRow = Absensi::selectRaw($this->statusSumSelectRaw())
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $currentMonth)
            ->where('soft_delete', 0);

        $bulanRow = $this->applySiteFilter($bulanRow, $idSitusArray, $isAllSite)->first();

        $bulanTelat      = (int) ($bulanRow->telat ?? 0);
        $bulanSakit      = (int) ($bulanRow->sakit ?? 0);
        $bulanIzin       = (int) ($bulanRow->izin ?? 0);
        $bulanTanpaKabar = (int) ($bulanRow->tanpa_kabar ?? 0);
        $bulanCuti       = (int) ($bulanRow->cuti ?? 0);

        // Aggregat hari ini
        $today      = Carbon::today();
        $todayLabel = $today->translatedFormat('d F Y');

        $dailyRow = Absensi::selectRaw($this->statusSumSelectRaw())
            ->whereDate('tanggal', $today)
            ->where('soft_delete', 0);

        $dailyRow = $this->applySiteFilter($dailyRow, $idSitusArray, $isAllSite)->first();

        $dailyTelat      = (int) ($dailyRow->telat ?? 0);
        $dailySakit      = (int) ($dailyRow->sakit ?? 0);
        $dailyIzin       = (int) ($dailyRow->izin ?? 0);
        $dailyTanpaKabar = (int) ($dailyRow->tanpa_kabar ?? 0);
        $dailyCuti       = (int) ($dailyRow->cuti ?? 0);

        // Pie diagram (per site) untuk periode
        $defaultDiagramPeriode = ($currentMonth <= 6) ? 1 : 2;
        $diagramPeriodeKe      = (int) $request->query('diagram_periode', $defaultDiagramPeriode);

        if ($diagramPeriodeKe === 1) {
            $startDiagram = 1;
            $endDiagram   = 6;
        } else {
            $diagramPeriodeKe = 2;
            $startDiagram     = 7;
            $endDiagram       = 12;
        }

        [$diagramLabels, $diagramTotals, $diagramDetail]
            = $this->getPeriodePieAggregates($year, $startDiagram, $endDiagram, $idSitusArray, $isAllSite);

        return compact(
            'labels',
            'bulanNumbers',
            'dataTelat',
            'dataSakit',
            'dataIzin',
            'dataTanpaKabar',
            'dataCuti',
            'bulanSekarangLabel',
            'bulanTelat',
            'bulanSakit',
            'bulanIzin',
            'bulanTanpaKabar',
            'bulanCuti',
            'todayLabel',
            'dailyTelat',
            'dailySakit',
            'dailyIzin',
            'dailyTanpaKabar',
            'dailyCuti',
            'diagramLabels',
            'diagramTotals',
            'diagramDetail',
            'periodeKe',
            'diagramPeriodeKe'
        );
    }

    private function buildPerbandinganData(
        int $year,
        array $allMonths,
        array $idSitusArray,
        bool $isAllSite
    ): array {
        $buildForRange = function (int $startMonth, int $endMonth) use (
            $year,
            $allMonths,
            $idSitusArray,
            $isAllSite
        ) {
            [
                $labels,
                $bulanNumbers,
                $telat,
                $sakit,
                $izin,
                $tanpa,
                $cuti
            ] = $this->getPeriodeBarAggregates(
                $year,
                $startMonth,
                $endMonth,
                $allMonths,
                $idSitusArray,
                $isAllSite
            );

            [$diagramLabels, $diagramTotals, $diagramDetail]
                = $this->getPeriodePieAggregates(
                    $year,
                    $startMonth,
                    $endMonth,
                    $idSitusArray,
                    $isAllSite
                );

            return [
                'labels'        => $labels,
                'bulanNumbers'  => $bulanNumbers,
                'telat'         => $telat,
                'sakit'         => $sakit,
                'izin'          => $izin,
                'tanpa'         => $tanpa,
                'cuti'          => $cuti,
                'diagramLabels' => $diagramLabels,
                'diagramTotals' => $diagramTotals,
                'diagramDetail' => $diagramDetail,
            ];
        };

        $p1 = $buildForRange(1, 6);
        $p2 = $buildForRange(7, 12);

        return [
            'cmpLabels1'        => $p1['labels'],
            'cmpBulanNumbers1'  => $p1['bulanNumbers'],
            'cmpTelat1'         => $p1['telat'],
            'cmpSakit1'         => $p1['sakit'],
            'cmpIzin1'          => $p1['izin'],
            'cmpTanpa1'         => $p1['tanpa'],
            'cmpCuti1'          => $p1['cuti'],
            'cmpDiagramLabels1' => $p1['diagramLabels'],
            'cmpDiagramTotals1' => $p1['diagramTotals'],
            'cmpDiagramDetail1' => $p1['diagramDetail'],

            'cmpLabels2'        => $p2['labels'],
            'cmpBulanNumbers2'  => $p2['bulanNumbers'],
            'cmpTelat2'         => $p2['telat'],
            'cmpSakit2'         => $p2['sakit'],
            'cmpIzin2'          => $p2['izin'],
            'cmpTanpa2'         => $p2['tanpa'],
            'cmpCuti2'          => $p2['cuti'],
            'cmpDiagramLabels2' => $p2['diagramLabels'],
            'cmpDiagramTotals2' => $p2['diagramTotals'],
            'cmpDiagramDetail2' => $p2['diagramDetail'],
        ];
    }

    private function getPeriodeBarAggregates(
        int $year,
        int $startMonth,
        int $endMonth,
        array $allMonths,
        array $idSitusArray,
        bool $isAllSite
    ): array {
        $labels       = [];
        $bulanMap     = [];
        $bulanNumbers = [];
        $i            = 0;

        for ($m = $startMonth; $m <= $endMonth; $m++) {
            $labels[]       = $allMonths[$m];
            $bulanMap[$m]   = $i;
            $bulanNumbers[] = $m;
            $i++;
        }

        $len            = $endMonth - $startMonth + 1;
        $dataTelat      = array_fill(0, $len, 0);
        $dataSakit      = array_fill(0, $len, 0);
        $dataIzin       = array_fill(0, $len, 0);
        $dataTanpaKabar = array_fill(0, $len, 0);
        $dataCuti       = array_fill(0, $len, 0);

        $rows = Absensi::selectRaw("
                YEAR(tanggal)  as tahun,
                MONTH(tanggal) as bulan,
                {$this->statusSumSelectRaw()}
            ")
            ->whereYear('tanggal', $year)
            ->whereBetween(DB::raw('MONTH(tanggal)'), [$startMonth, $endMonth])
            ->where('soft_delete', 0);

        $rows = $this->applySiteFilter($rows, $idSitusArray, $isAllSite)
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get();

        foreach ($rows as $row) {
            if (!isset($bulanMap[$row->bulan])) {
                continue;
            }
            $idx = $bulanMap[$row->bulan];

            $dataTelat[$idx]      = (int) $row->telat;
            $dataSakit[$idx]      = (int) $row->sakit;
            $dataIzin[$idx]       = (int) $row->izin;
            $dataTanpaKabar[$idx] = (int) $row->tanpa_kabar;
            $dataCuti[$idx]       = (int) $row->cuti;
        }

        return [
            $labels,
            $bulanNumbers,
            $dataTelat,
            $dataSakit,
            $dataIzin,
            $dataTanpaKabar,
            $dataCuti,
        ];
    }

    private function getPeriodePieAggregates(
        int $year,
        int $startMonth,
        int $endMonth,
        array $idSitusArray,
        bool $isAllSite
    ): array {
        $query = Absensi::from('tbhs_absensi as a')
            ->join('tbhs_situs as s', 's.id', '=', 'a.id_situs')
            ->selectRaw("
                a.id_situs,
                s.nama_situs,
                {$this->statusSumSelectRaw('a')}
            ")
            ->whereYear('a.tanggal', $year)
            ->whereBetween(DB::raw('MONTH(a.tanggal)'), [$startMonth, $endMonth])
            ->where('a.soft_delete', 0);

        $query = $this->applySiteFilter($query, $idSitusArray, $isAllSite, 'a.id_situs');

        $rows = $query
            ->groupBy('a.id_situs', 's.nama_situs')
            ->orderBy('s.nama_situs')
            ->get();

        $labels = [];
        $totals = [];
        $detail = [];

        foreach ($rows as $row) {
            $nama       = $row->nama_situs;
            $telat      = (int) $row->telat;
            $sakit      = (int) $row->sakit;
            $izin       = (int) $row->izin;
            $tanpaKabar = (int) $row->tanpa_kabar;
            $cuti       = (int) $row->cuti;

            $total = $telat + $sakit + $izin + $tanpaKabar + $cuti;

            $labels[] = $nama;
            $totals[] = $total;

            $detail[$nama] = [
                'telat'       => $telat,
                'sakit'       => $sakit,
                'izin'        => $izin,
                'tanpa_kabar' => $tanpaKabar,
                'cuti'        => $cuti,
            ];
        }

        return [$labels, $totals, $detail];
    }

    /* ==========================
     * GRAFIK DETAIL (MODAL)
     * ========================== */

    public function grafikDetailAbsensi(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) {
            return $resp;
        }

        $status = $request->get('status');
        $bulan  = (int) $request->get('bulan');
        $year   = now()->year;

        [, $idSitusArray, $isAllSite] = $this->resolveUserSiteScope();

        $query = Absensi::from('tbhs_absensi as a')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, a.id_situs)'), '>', DB::raw('0'));
            })
            ->whereYear('a.tanggal', $year)
            ->whereMonth('a.tanggal', $bulan)
            ->where('a.soft_delete', 0);

        if ($status) {
            $query->where('a.status', 'LIKE', "%{$status}%");
        }

        $query = $this->applySiteFilter($query, $idSitusArray, $isAllSite, 'a.id_situs');

        $rows = $query
            ->groupBy(
                'a.id',
                'a.id_admin',
                'a.nama_staff',
                'a.id_situs',
                'a.tanggal',
                'a.status',
                'a.remarks',
                'a.cuti_start',
                'a.cuti_end'
            )
            ->selectRaw('
                a.id,
                a.id_admin,
                a.nama_staff,
                a.id_situs,
                a.tanggal,
                a.status,
                a.remarks,
                a.cuti_start,
                a.cuti_end,
                GROUP_CONCAT(s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->orderBy('a.tanggal', 'desc')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function grafikDetailSitus(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) {
            return $resp;
        }

        $namaSitus = $request->get('situs');
        $year      = now()->year;

        [, $idSitusArray, $isAllSite] = $this->resolveUserSiteScope();

        $now          = Carbon::now();
        $currentMonth = $now->month;
        $startMonth   = ($currentMonth <= 6) ? 1 : 7;
        $endMonth     = $startMonth + 5;

        $query = Absensi::from('tbhs_absensi as a')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, a.id_situs)'), '>', DB::raw('0'));
            })
            ->whereYear('a.tanggal', $year)
            ->whereBetween(DB::raw('MONTH(a.tanggal)'), [$startMonth, $endMonth])
            ->where('a.soft_delete', 0);

        if ($namaSitus) {
            $query->where('s.nama_situs', $namaSitus);
        }

        $query = $this->applySiteFilter($query, $idSitusArray, $isAllSite, 'a.id_situs');

        $rows = $query
            ->groupBy(
                'a.id',
                'a.id_admin',
                'a.nama_staff',
                'a.tanggal',
                'a.status',
                'a.remarks',
                'a.cuti_start',
                'a.cuti_end'
            )
            ->selectRaw('
                a.id,
                a.id_admin,
                a.nama_staff,
                a.tanggal,
                a.status,
                a.remarks,
                a.cuti_start,
                a.cuti_end,
                GROUP_CONCAT(s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->orderBy('a.tanggal', 'desc')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function grafikDailyDetail(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) {
            return $resp;
        }

        $status = $request->get('status');
        $today  = Carbon::today();

        [, $idSitusArray, $isAllSite] = $this->resolveUserSiteScope();

        $query = Absensi::from('tbhs_absensi as a')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, a.id_situs)'), '>', DB::raw('0'));
            })
            ->whereDate('a.tanggal', $today)
            ->where('a.soft_delete', 0);

        if ($status) {
            $query->where('a.status', 'LIKE', "%{$status}%");
        }

        $query = $this->applySiteFilter($query, $idSitusArray, $isAllSite, 'a.id_situs');

        $rows = $query
            ->groupBy(
                'a.id',
                'a.id_admin',
                'a.nama_staff',
                'a.tanggal',
                'a.status',
                'a.remarks',
                'a.cuti_start',
                'a.cuti_end'
            )
            ->selectRaw('
                a.id,
                a.id_admin,
                a.nama_staff,
                a.tanggal,
                a.status,
                a.remarks,
                a.cuti_start,
                a.cuti_end,
                GROUP_CONCAT(s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->orderBy('a.tanggal', 'desc')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function grafikCompareData(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) {
            return $resp;
        }

        [, $idSitusArray, $isAllSite] = $this->resolveUserSiteScope();

        $year      = (int) $request->query('year', now()->year);
        $allMonths = $this->monthNames();

        $compareData = $this->buildPerbandinganData(
            $year,
            $allMonths,
            $idSitusArray,
            $isAllSite
        );

        return response()->json($compareData);
    }
}
