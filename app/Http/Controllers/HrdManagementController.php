<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AbsensiReportExport;
use App\Helpers\AuthLink;

use App\Models\User; // Asumsi model User
use App\Models\Daftarsitus; // Asumsi model Daftarsitus
use App\Models\Absensi; // <<< Asumsi Anda punya Model Absensi
use App\Models\AbsensiReport;

use Carbon\Carbon;

class HrdManagementController extends Controller
{


    // 🔹 1) Cek akses menu sekali, dipakai ulang
    private function ensureHrdAccess()
    {
        $user  = Auth::user();
        $akses = AuthLink::access_url($user->id_admin, 'hrdmanagement');

        if (empty($akses) || $akses[0]->nilai == 0) {
            // boleh pakai abort(403) atau view('error'), sesuaikan project-mu
            return view('error');
        }

        return null; // artinya OK
    }

    // 🔹 2) Ambil info situs user: namaSitusLogin, idSitusArray, isAllSite
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
                $idSitusPertama = $idSitusArray[0];
                $situs          = Daftarsitus::find($idSitusPertama);

                if ($situs) {
                    $namaSitusLogin = $situs->nama_situs;
                }
            }
        }

        return [$namaSitusLogin, $idSitusArray, $isAllSite, $user];
    }

    // 🔹 3) Apply filter situs ke query Eloquent/Query Builder
    private function applySiteFilter($query, array $idSitusArray, bool $isAllSite, string $column = 'id_situs')
    {
        return $query->when(!$isAllSite && !empty($idSitusArray), function ($q) use ($idSitusArray, $column) {
            $q->whereIn($column, $idSitusArray);
        });
    }

     /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) {
            return $resp;
        }

        [$namaSitusLogin, $idSitusArray, $isAllSite, $userLogin] = $this->resolveUserSiteScope();

        $filteredUsers = collect();

        if ($userLogin && !empty($idSitusArray)) {
            if ($isAllSite) {
                $filteredUsers = User::where('status', 1) // 🔹 hanya user aktif
                    ->select('id', 'id_admin', 'nama_staff', 'id_situs')
                    ->get();
            } else {
                $filteredUsers = User::where('status', 1)  // 🔹 hanya user aktif
                    ->where(function ($query) use ($idSitusArray) {
                        foreach ($idSitusArray as $idSitus) {
                            $query->orWhereRaw("FIND_IN_SET(?, id_situs)", [trim($idSitus)]);
                        }
                    })
                    ->select('id', 'id_admin', 'nama_staff', 'id_situs')
                    ->get();
            }
        }

        return view('pages.hrdmanagement.index', compact('namaSitusLogin', 'filteredUsers'));
    }

    public function updateAbsensi(Request $request, $id)
    {
        $request->validate([
            'id_admin'   => 'required',
            'id_situs'   => 'required|string|max:50', // ⬅ tadinya integer
            'nama_staff' => 'required|string|max:100',
            'tanggal'    => 'required|date',
            'kehadiran'  => 'required|array',
            'remarks'    => 'nullable|string|max:255',
            'cuti_start' => 'nullable|date',
            'cuti_end'   => 'nullable|date|after_or_equal:cuti_start',
        ]);

        $statusArray  = $request->input('kehadiran', []);
        $statusString = $statusArray ? implode(', ', $statusArray) : 'HADIR';

        $absensi  = Absensi::findOrFail($id);
        $editedBy = Auth::user()->id_admin ?? Auth::id();

        $isCuti   = in_array('CUTI', $statusArray);
        $hasRange = $request->filled('cuti_start') && $request->filled('cuti_end');

        $data = [
            'nama_staff' => $request->nama_staff,
            'id_situs'   => $request->id_situs,   // ⬅ bisa "3" atau "3,34"
            'status'     => $statusString,
            'remarks'    => $request->remarks,
            'edited_by'  => $editedBy,
        ];

        if ($isCuti && $hasRange) {
            $data['tanggal']    = $request->tanggal;
            $data['cuti_start'] = $request->cuti_start;
            $data['cuti_end']   = $request->cuti_end;
        } else {
            $data['tanggal']    = $request->tanggal;
            $data['cuti_start'] = null;
            $data['cuti_end']   = null;
        }

        $absensi->update($data);

        // kalau request dari AJAX, balas JSON
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data absensi berhasil diupdate.',
            ]);
        }

        // fallback submit biasa
        return redirect()->back()->with('success', 'Data absensi berhasil diupdate.');
    }


    public function storeAbsensi(Request $request)
    {
        $request->validate([
            'id_admin'   => 'required|string|max:50',
            'id_situs'   => 'required|string|max:50', // ⬅ tadinya integer
            'nama_staff' => 'required|string|max:100',
            'tanggal'    => 'required|date',
            'remarks'    => 'nullable|string',
            'kehadiran'  => 'nullable|array',
            'cuti_start' => 'nullable|date',
            'cuti_end'   => 'nullable|date|after_or_equal:cuti_start',
        ]);

        $statusArray  = $request->input('kehadiran');
        $statusString = 'HADIR';

        if (!empty($statusArray)) {
            $statusString = implode(', ', $statusArray);
        }

        $userCode = Auth::user()->id_admin ?? Auth::id();

        $isCuti   = !empty($statusArray) && in_array('CUTI', $statusArray);
        $hasRange = $request->filled('cuti_start') && $request->filled('cuti_end');

        $data = [
            'id_admin'   => $request->id_admin,
            'id_situs'   => $request->id_situs,  // ⬅ boleh "3,34"
            'nama_staff' => $request->nama_staff,
            'status'     => $statusString,
            'remarks'    => $request->remarks,
            'created_by' => $userCode,
            'edited_by'  => null,
        ];

        if ($isCuti && $hasRange) {
            $data['tanggal']    = $request->tanggal;
            $data['cuti_start'] = $request->cuti_start;
            $data['cuti_end']   = $request->cuti_end;
        } else {
            $data['tanggal']    = $request->tanggal;
            $data['cuti_start'] = null;
            $data['cuti_end']   = null;
        }

        $absensi = Absensi::create($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Absensi berhasil disimpan!',
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Absensi berhasil disimpan!');
    }


    public function listAbsensi(Request $request)
    {
        $request->validate([
            'id_admin' => 'required',
        ]);

        $today    = Carbon::today();
        $lastWeek = $today->copy()->subDays(6);

        $absensi = Absensi::from('tbhs_absensi as a')
            ->join('tbhs_users as u', 'u.id_admin', '=', 'a.id_admin')
            ->leftJoin('tbhs_situs as s', function ($join) {
                // s.id dicocokkan ke string u.id_situs, misal "4,34"
                $join->on(DB::raw('FIND_IN_SET(s.id, u.id_situs)'), '>', DB::raw('0'));
            })
            ->where('a.id_admin', $request->id_admin)
            // kalau masih mau filter by id_situs tertentu:
            ->when($request->filled('id_situs'), function ($q) use ($request) {
                // filter di level absensi (a.id_situs)
                $q->where('a.id_situs', $request->id_situs);
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
                'a.cuti_end',
                'u.id_situs'
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
                GROUP_CONCAT(s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->get();

        return response()->json($absensi);
    }

    public function destroyAbsensi($id)
        {
            $absensi = Absensi::find($id);

            if (! $absensi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data absensi tidak ditemukan.',
                ], 404);
            }

            $absensi->delete();

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
                ->exists();

            return response()->json([
                'exists' => $exists,
            ]);
        }    

    public function staffData()
    {
        [$namaSitusLogin, $idSitusArray, $isAllSite, $userLogin] = $this->resolveUserSiteScope();

        $rows = [];

        if ($userLogin && !empty($idSitusArray)) {
            if ($isAllSite) {
                $filteredUsers = User::where('status', 1) // 🔹 hanya user aktif
                    ->select('id', 'id_admin', 'nama_staff', 'id_situs')
                    ->get();
            } else {
                $filteredUsers = User::where('status', 1) // 🔹 hanya user aktif
                    ->where(function ($query) use ($idSitusArray) {
                        foreach ($idSitusArray as $idSitus) {
                            $query->orWhereRaw("FIND_IN_SET(?, id_situs)", [trim($idSitus)]);
                        }
                    })
                    ->select('id', 'id_admin', 'nama_staff', 'id_situs')
                    ->get();
            }

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
                    'id_situs'       => $user->id_situs, // <<=== "4,34"
                ];
            }
        }

        return response()->json(['data' => $rows]);
    }

    public function reportingAbsensi()
    {
        $user = Auth::user();

        // 🔐 Cek privilege menu hrdmanagement
        $akses = AuthLink::access_url($user->id_admin, 'hrdmanagement');
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $sites = Daftarsitus::select('id', 'nama_situs')
            ->orderBy('nama_situs')
            ->get();

        return view('pages.hrdmanagement.reportingabsensi', compact('sites'));
    }
    

    public function getReportingAbsensiData(Request $request)
    {
        $idSitus = $request->input('id_situs');
        $periode = $request->input('periode');   // "2025-1" atau "2025-2" atau null
        $tahun   = $request->input('tahun');     // "2025" atau null

        $rows = DB::table('tbhs_absensireport as r')
            ->join('tbhs_users as u', 'u.id_admin', '=', 'r.id_admin')
            ->leftJoin('tbhs_situs as s', function ($join) {
                // u.id_situs bisa "4,34" → cocokan s.id satu per satu
                $join->on(DB::raw('FIND_IN_SET(s.id, u.id_situs)'), '>', DB::raw('0'));
            })
            ->when($idSitus, function ($q) use ($idSitus) {
                $q->where('r.id_situs', $idSitus);
            })
            ->when($periode, function ($q) use ($periode) {
                $q->where('r.periode', $periode);
            })
            ->when(!$periode && $tahun, function ($q) use ($tahun) {
                $q->where('r.periode', 'like', $tahun.'-%');
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
            ->get();

        return response()->json([
            'data' => $rows,
        ]);
    }



    public function generateAbsensiReport(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'periode'  => 'required|string',   // contoh: "2025-1"
        ]);

        $periode = $request->input('periode');

        // 2. Amankan pecahan "tahun-periode"
        $parts = explode('-', $periode);
        if (count($parts) !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Format periode tidak valid. Gunakan format: TAHUN-PERIODE, contoh: 2025-1',
            ], 422);
        }

        $tahun     = (int) $parts[0];
        $periodeKe = (int) $parts[1];

        if ($periodeKe === 1) {
            $start = Carbon::create($tahun, 1, 1)->toDateString();   // 1 Jan
            $end   = Carbon::create($tahun, 6, 30)->toDateString();  // 30 Jun
        } elseif ($periodeKe === 2) {
            $start = Carbon::create($tahun, 7, 1)->toDateString();   // 1 Jul
            $end   = Carbon::create($tahun, 12, 31)->toDateString(); // 31 Des
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Periode harus 1 atau 2.',
            ], 422);
        }

        // 3. Ambil & agregasi semua karyawan dari tbhs_absensi
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
            ->groupBy('a.id_admin', 'a.nama_staff', 'a.id_situs')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data absensi untuk periode ini.',
            ]);
        }

        // 4. Simpan/update ke tbhs_absensireport
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
            'id_situs'   => 'nullable|integer',
        ]);

        $tahun     = (string) $request->query('tahun');
        $periodeKe = $request->query('periode_ke'); // '1' / '2' / null
        $idSitus   = $request->query('id_situs');   // int / null

        $periode = $periodeKe ? ($tahun . '-' . $periodeKe) : null;

        $suffixPeriode = $periodeKe ? ('_periode_'.$periodeKe) : '_all_periode';
        $suffixSitus   = $idSitus   ? ('_situs_'.$idSitus)    : '_all_situs';

        $fileName = 'report_absensi_' . $tahun . $suffixPeriode . $suffixSitus . '.xlsx';

        return Excel::download(
            new AbsensiReportExport($tahun, $periode, $idSitus ? (int)$idSitus : null),
            $fileName
        );
    }


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

        $allMonths = [
            null,
            'Januari','Februari','Maret','April','Mei','Juni',
            'Juli','Agustus','September','Oktober','November','Desember',
        ];

            // ================== DAFTAR TAHUN DARI tbhs_absensi ==================
        $yearQuery = Absensi::selectRaw('DISTINCT YEAR(tanggal) as tahun');

        // kalau mau ikut filter situs seperti grafik lain:
        $yearQuery = $this->applySiteFilter($yearQuery, $idSitusArray, $isAllSite, 'id_situs');

        $compareYears = $yearQuery
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->toArray();

        // default tahun untuk compare = tahun sekarang
        $compareYear = (int) $request->query('compare_year', $year);

        // ================== DATA TAB 1 (GRAFIK) ==================
        $mainData = $this->buildMainGrafikData(
            $request,
            $year,
            $currentMonth,
            $allMonths,
            $idSitusArray,
            $isAllSite
        );

        // ================== DATA TAB 2 (PERBANDINGAN) ==================
        $compareData = $this->buildPerbandinganData(
            $compareYear,   // ← PAKAI TAHUN YANG DIPILIH
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

/**
 * Data untuk TAB 1 (grafik utama seperti sebelumnya).
 */
    private function buildMainGrafikData(
        Request $request,
        int $year,
        int $currentMonth,
        array $allMonths,
        array $idSitusArray,
        bool $isAllSite
    ): array {
        // ====== PERIODE GRAFIK BATANG ======
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

        // bar utama
        [$labels, $bulanNumbers, $dataTelat, $dataSakit, $dataIzin, $dataTanpaKabar, $dataCuti]
            = $this->getPeriodeBarAggregates($year, $startMonth, $endMonth, $allMonths, $idSitusArray, $isAllSite);

        // 1 bulan sekarang
        $bulanSekarangLabel = $allMonths[$currentMonth];

        $bulanRow = Absensi::selectRaw("
                        SUM(CASE WHEN status LIKE '%TELAT%'       THEN 1 ELSE 0 END) as telat,
                        SUM(CASE WHEN status LIKE '%SAKIT%'       THEN 1 ELSE 0 END) as sakit,
                        SUM(CASE WHEN status LIKE '%IZIN%'        THEN 1 ELSE 0 END) as izin,
                        SUM(CASE WHEN status LIKE '%TANPA KABAR%' THEN 1 ELSE 0 END) as tanpa_kabar,
                        SUM(CASE WHEN status LIKE '%CUTI%'        THEN 1 ELSE 0 END) as cuti
                    ")
                    ->whereYear('tanggal', $year)
                    ->whereMonth('tanggal', $currentMonth);

        $bulanRow = $this->applySiteFilter($bulanRow, $idSitusArray, $isAllSite)->first();

        $bulanTelat      = (int) ($bulanRow->telat ?? 0);
        $bulanSakit      = (int) ($bulanRow->sakit ?? 0);
        $bulanIzin       = (int) ($bulanRow->izin ?? 0);
        $bulanTanpaKabar = (int) ($bulanRow->tanpa_kabar ?? 0);
        $bulanCuti       = (int) ($bulanRow->cuti ?? 0);

        // DAILY
        $today      = Carbon::today();
        $todayLabel = $today->translatedFormat('d F Y');

        $dailyRow = Absensi::selectRaw("
                        SUM(CASE WHEN status LIKE '%TELAT%'       THEN 1 ELSE 0 END) as telat,
                        SUM(CASE WHEN status LIKE '%SAKIT%'       THEN 1 ELSE 0 END) as sakit,
                        SUM(CASE WHEN status LIKE '%IZIN%'        THEN 1 ELSE 0 END) as izin,
                        SUM(CASE WHEN status LIKE '%TANPA KABAR%' THEN 1 ELSE 0 END) as tanpa_kabar,
                        SUM(CASE WHEN status LIKE '%CUTI%'        THEN 1 ELSE 0 END) as cuti
                    ")
                    ->whereDate('tanggal', $today);

        $dailyRow = $this->applySiteFilter($dailyRow, $idSitusArray, $isAllSite)->first();

        $dailyTelat      = (int) ($dailyRow->telat ?? 0);
        $dailySakit      = (int) ($dailyRow->sakit ?? 0);
        $dailyIzin       = (int) ($dailyRow->izin ?? 0);
        $dailyTanpaKabar = (int) ($dailyRow->tanpa_kabar ?? 0);
        $dailyCuti       = (int) ($dailyRow->cuti ?? 0);

        // DIAGRAM SITUS (ikut parameter diagram_periode)
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

/**
 * Data untuk TAB 2 (perbandingan periode 1 & 2).
 */
    private function buildPerbandinganData(
        int $year,
        array $allMonths,
        array $idSitusArray,
        bool $isAllSite
    ): array {
        // Periode 1: Jan–Jun
        [$cmpLabels1, $cmpBulanNumbers1, $cmpTelat1, $cmpSakit1, $cmpIzin1, $cmpTanpa1, $cmpCuti1]
            = $this->getPeriodeBarAggregates($year, 1, 6, $allMonths, $idSitusArray, $isAllSite);

        [$cmpDiagramLabels1, $cmpDiagramTotals1, $cmpDiagramDetail1]
            = $this->getPeriodePieAggregates($year, 1, 6, $idSitusArray, $isAllSite);

        // Periode 2: Jul–Des
        [$cmpLabels2, $cmpBulanNumbers2, $cmpTelat2, $cmpSakit2, $cmpIzin2, $cmpTanpa2, $cmpCuti2]
            = $this->getPeriodeBarAggregates($year, 7, 12, $allMonths, $idSitusArray, $isAllSite);

        [$cmpDiagramLabels2, $cmpDiagramTotals2, $cmpDiagramDetail2]
            = $this->getPeriodePieAggregates($year, 7, 12, $idSitusArray, $isAllSite);

        return compact(
            'cmpLabels1','cmpBulanNumbers1','cmpTelat1','cmpSakit1','cmpIzin1','cmpTanpa1','cmpCuti1',
            'cmpDiagramLabels1','cmpDiagramTotals1','cmpDiagramDetail1',
            'cmpLabels2','cmpBulanNumbers2','cmpTelat2','cmpSakit2','cmpIzin2','cmpTanpa2','cmpCuti2',
            'cmpDiagramLabels2','cmpDiagramTotals2','cmpDiagramDetail2'
        );
    }

/**
 * Helper umum: agregasi BAR per periode (bulan start–end).
 */
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
                    YEAR(tanggal) as tahun,
                    MONTH(tanggal) as bulan,
                    SUM(CASE WHEN status LIKE '%TELAT%'       THEN 1 ELSE 0 END) as telat,
                    SUM(CASE WHEN status LIKE '%SAKIT%'       THEN 1 ELSE 0 END) as sakit,
                    SUM(CASE WHEN status LIKE '%IZIN%'        THEN 1 ELSE 0 END) as izin,
                    SUM(CASE WHEN status LIKE '%TANPA KABAR%' THEN 1 ELSE 0 END) as tanpa_kabar,
                    SUM(CASE WHEN status LIKE '%CUTI%'        THEN 1 ELSE 0 END) as cuti
                ")
                ->whereYear('tanggal', $year)
                ->whereBetween(DB::raw('MONTH(tanggal)'), [$startMonth, $endMonth]);

        $rows = $this->applySiteFilter($rows, $idSitusArray, $isAllSite)
                ->groupBy('tahun','bulan')
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->get();

        foreach ($rows as $row) {
            if (!isset($bulanMap[$row->bulan])) continue;
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

/**
 * Helper umum: agregasi PIE per periode (bulan start–end).
 */
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
                SUM(CASE WHEN a.status LIKE '%TELAT%'       THEN 1 ELSE 0 END) as telat,
                SUM(CASE WHEN a.status LIKE '%SAKIT%'       THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN a.status LIKE '%IZIN%'        THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN a.status LIKE '%TANPA KABAR%' THEN 1 ELSE 0 END) as tanpa_kabar,
                SUM(CASE WHEN a.status LIKE '%CUTI%'        THEN 1 ELSE 0 END) as cuti
            ")
            ->whereYear('a.tanggal', $year)
            ->whereBetween(DB::raw('MONTH(a.tanggal)'), [$startMonth, $endMonth]);

        $query = $this->applySiteFilter($query, $idSitusArray, $isAllSite, 'a.id_situs');

        $rows = $query
            ->groupBy('a.id_situs','s.nama_situs')
            ->orderBy('s.nama_situs')
            ->get();

        $labels = [];
        $totals = [];
        $detail = [];

        foreach ($rows as $row) {
            $nama        = $row->nama_situs;
            $telat       = (int) $row->telat;
            $sakit       = (int) $row->sakit;
            $izin        = (int) $row->izin;
            $tanpaKabar  = (int) $row->tanpa_kabar;
            $cuti        = (int) $row->cuti;

            $total = $telat + $sakit + $izin + $tanpaKabar + $cuti;

            $labels[] = $nama;
            $totals[] = $total;

            // 🔹 pakai key 'tanpa_kabar' supaya cocok dengan JS
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
    

    public function grafikDetailAbsensi(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) {
            return $resp;
        }

        $status = $request->get('status');        // TELAT | SAKIT | IZIN | TANPA KABAR | CUTI
        $bulan  = (int) $request->get('bulan');   // 1..12
        $year   = now()->year;

        [$namaSitusLogin, $idSitusArray, $isAllSite] = $this->resolveUserSiteScope();

        $query = Absensi::from('tbhs_absensi as a')
            ->join('tbhs_users as u', 'u.id_admin', '=', 'a.id_admin')
            ->leftJoin('tbhs_situs as s', function ($join) {
                // s.id dicari di string u.id_situs (misal "4,34")
                $join->on(DB::raw('FIND_IN_SET(s.id, u.id_situs)'), '>', DB::raw('0'));
            })
            ->whereYear('a.tanggal', $year)
            ->whereMonth('a.tanggal', $bulan);

        if ($status) {
            $query->where('a.status', 'LIKE', "%{$status}%");
        }

        // tetap pakai filter site berdasarkan a.id_situs (integer)
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
                'u.id_situs'
            )
            ->selectRaw('
                a.id,
                a.id_admin,
                a.nama_staff,
                a.id_situs,
                a.tanggal,
                a.status,
                a.remarks,
                GROUP_CONCAT(s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->orderBy('a.tanggal', 'desc')
            ->get();

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function grafikDetailSitus(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) {
            return $resp;
        }

        $namaSitus = $request->get('situs');
        $year      = now()->year;

        [$namaSitusLogin, $idSitusArray, $isAllSite] = $this->resolveUserSiteScope();

        $now          = Carbon::now();
        $currentMonth = $now->month;
        $startMonth   = ($currentMonth <= 6) ? 1 : 7;
        $endMonth     = $startMonth + 5;

        $query = Absensi::from('tbhs_absensi as a')
            ->join('tbhs_users as u', 'u.id_admin', '=', 'a.id_admin')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, u.id_situs)'), '>', DB::raw('0'));
            })
            ->whereYear('a.tanggal', $year)
            ->whereBetween(DB::raw('MONTH(a.tanggal)'), [$startMonth, $endMonth]);

        if ($namaSitus) {
            // filter berdasarkan nama_situs hasil join (yang bisa dari 4,34 → "Situs A, Situs B")
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
                'u.id_situs'
            )
            ->selectRaw('
                a.id,
                a.id_admin,
                a.nama_staff,
                a.tanggal,
                a.status,
                a.remarks,
                GROUP_CONCAT(s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->orderBy('a.tanggal', 'desc')
            ->get();

        return response()->json([
            'data' => $rows,
        ]);
    }


    public function grafikDailyDetail(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) {
            return $resp;
        }

        $status = $request->get('status');
        $today  = Carbon::today();

        [$namaSitusLogin, $idSitusArray, $isAllSite] = $this->resolveUserSiteScope();

        $query = Absensi::from('tbhs_absensi as a')
            ->join('tbhs_users as u', 'u.id_admin', '=', 'a.id_admin')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, u.id_situs)'), '>', DB::raw('0'));
            })
            ->whereDate('a.tanggal', $today);

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
                'u.id_situs'
            )
            ->selectRaw('
                a.id,
                a.id_admin,
                a.nama_staff,
                a.tanggal,
                a.status,
                a.remarks,
                GROUP_CONCAT(s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->orderBy('a.tanggal', 'desc')
            ->get();

        return response()->json([
            'data' => $rows,
        ]);
    }
    
    public function grafikCompareData(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) {
            return $resp;
        }

        [$namaSitusLogin, $idSitusArray, $isAllSite, $userLogin] = $this->resolveUserSiteScope();

        $year = (int) $request->query('year', now()->year);

        $allMonths = [
            null,
            'Januari','Februari','Maret','April','Mei','Juni',
            'Juli','Agustus','September','Oktober','November','Desember',
        ];

        $compareData = $this->buildPerbandinganData(
            $year,
            $allMonths,
            $idSitusArray,
            $isAllSite
        );

        return response()->json($compareData);   // berisi cmpLabels1, cmpTelat1, dst
    }


    public function detailAbsensi(Request $request)
    {
        $request->validate([
            'id_admin' => 'required',
        ]);

        $absensi = Absensi::from('tbhs_absensi as a')
            ->join('tbhs_users as u', 'u.id_admin', '=', 'a.id_admin')
            ->leftJoin('tbhs_situs as s', function ($join) {
                // s.id dicari di dalam string u.id_situs (misal: "4,34")
                $join->on(DB::raw('FIND_IN_SET(s.id, u.id_situs)'), '>', DB::raw('0'));
            })
            ->where('a.id_admin', $request->id_admin)
            ->orderBy('a.tanggal', 'desc')
            ->orderBy('a.id', 'desc')
            ->groupBy(
                'a.id',
                'a.id_admin',
                'a.tanggal',
                'a.status',
                'a.remarks',
                'u.id_situs'
            )
            ->selectRaw('
                a.id,
                a.id_admin,
                a.tanggal,
                a.status,
                a.remarks,
                u.id_situs,
                GROUP_CONCAT(s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->get();

        return response()->json($absensi);
    }


}