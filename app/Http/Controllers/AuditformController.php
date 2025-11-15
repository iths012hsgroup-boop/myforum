<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;        // <-- penting: ada titik koma
use Illuminate\Support\Facades\Schema;    // <-- huruf S besar
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;

use Yajra\DataTables\DataTables;
use Yajra\DataTables\Html\Builder;

use App\Helpers\AuthLink;
use App\Helpers\ImagesUpload;
use App\Models\User;
use App\Models\Forumaudit;
use App\Models\Forumauditpost;
use App\Models\SettingPeriode;
use App\Models\Privilegeaccess;
use App\Models\Topik;
use App\Models\Daftarsitus;
use App\Models\Absensi;
use App\Models\ViewAbsensi; // pastikan di atas controller

class AuditformController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public $id;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $baseQuery = Forumaudit::query()
            ->where('created_for', $user->id_admin)
            ->where('created_for_name', $user->nama_staff)
            ->where('soft_delete', '0');

        // Helper function untuk clone query dan filter
        $countCases = fn ($case, $fault = null, $periode = false) =>
            (clone $baseQuery)
                ->when($case, fn ($q) => $q->where('status_case', $case))
                ->when($fault !== null, fn ($q) =>
                    is_array($fault)
                        ? $q->whereIn('status_kesalahan', $fault)
                        : $q->where('status_kesalahan', $fault)
                )
                ->when($periode, fn ($q) => $q->where('periode', $periodeFilter))
                ->count();

        // Ambil semua hasil count
        $data = [
            'periodes' => SettingPeriode::all(),
            'case_open' => $countCases('1', '0'),
            'case_progress' => $countCases('2', '0'),
            'case_progress_aman' => $countCases('2', '1'),
            'case_progress_bersalah' => $countCases('2', [2, 3, 4]),
            'case_pending' => $countCases('3', [0, 1, 2, 3, 4]),
            'case_closed_aman' => $countCases('4', '1', true),
            'case_closed_bersalah_low' => $countCases('4', '2', true),
            'case_closed_bersalah_medium' => $countCases('4', '3', true),
            'case_closed_bersalah_high' => $countCases('4', '4', true),
        ];


        $today        = Carbon::now();
        $currentMonth = $today->copy()->startOfMonth();
        $idAdmin      = $user->id_admin;

        // === AMBIL DAFTAR BULAN DARI tbhs_viewabsensi (tahun sekarang) ===
        $viewMonths = ViewAbsensi::where('id_admin', $idAdmin)
            ->where('tahun', $today->year)
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->get();

        // === TENTUKAN BULAN YANG DITAMPILKAN SEKARANG ===
        $selected = $request->query('bulan');

        if ($selected) {
            try {
                $currentMonth = Carbon::createFromFormat('Y-m', $selected)->startOfMonth();
            } catch (\Exception $e) {
                $currentMonth = $today->copy()->startOfMonth();
            }
        }

        // === BANGUN DROPDOWN ===
        $dropdownMonths = [];

        // 1) SELALU MASUKKAN BULAN SEKARANG (meskipun belum ada di tbhs_viewabsensi)
        $dropdownMonths[] = [
            'key'   => $currentMonth->format('Y-m'),
            'nama'  => strtoupper($currentMonth->translatedFormat('F')),
            'tahun' => $currentMonth->year,
        ];

        // 2) TAMBAHKAN BULAN-BULAN YANG SUDAH ADA DI tbhs_viewabsensi
        foreach ($viewMonths as $vm) {
            // skip kalau itu bulan yg sama dengan currentMonth (biar tidak dobel)
            if ($vm->tahun == $currentMonth->year && $vm->bulan == $currentMonth->month) {
                continue;
            }

            $bulanObj = Carbon::create($vm->tahun, $vm->bulan, 1);

            $dropdownMonths[] = [
                'key'   => $bulanObj->format('Y-m'),
                'nama'  => strtoupper($bulanObj->translatedFormat('F')),
                'tahun' => $bulanObj->year,
            ];
        }

        // === TENTUKAN BULAN YANG DITAMPILKAN ===
        // ?bulan=YYYY-MM (contoh 2025-10), kalau tidak ada → pakai bulan SEKARANG
        $selected = $request->query('bulan');

        if ($selected) {
            try {
                $currentMonth = Carbon::createFromFormat('Y-m', $selected)->startOfMonth();
            } catch (\Exception $e) {
                // kalau format salah, tetap pakai bulan sekarang
            }
        }

        $tahunSekarang = $currentMonth->year;


                // Peta simbol dan warna absensi
                $symbolMap = [
                    'TELAT'        => ['icon' => 'T',  'color' => 'bg-warning text-dark'],
                    'SAKIT'        => ['icon' => 'S',  'color' => 'bg-primary text-white'],
                    'IZIN'         => ['icon' => 'I',  'color' => 'bg-info text-dark'],
                    'TANPA KABAR'  => ['icon' => 'TK', 'color' => 'bg-danger text-white'],
                    'CUTI'         => ['icon' => 'C',  'color' => 'bg-success text-dark'],
                ];

                // HANYA BULAN YANG DIPILIH
                $bulanList = [];
                $bulanObj = $currentMonth->copy();
                $bulanList[] = [
                    'nama'  => strtoupper($bulanObj->translatedFormat('F')),
                    'tahun' => $bulanObj->year,
                    'hari'  => $bulanObj->daysInMonth,
                    'month' => $bulanObj->month,
                ];

                // range tanggal bulan yang dipilih
                $startDate = $currentMonth->copy()->startOfMonth();
                $endDate   = $currentMonth->copy()->endOfMonth();

            $rawAbsensi = Absensi::where('id_admin', $user->id_admin)
                ->where('soft_delete', 0)   // ⬅️ hanya ambil yang belum dihapus
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->whereNotNull('cuti_start')
                            ->whereNotNull('cuti_end')
                            ->whereDate('cuti_start', '<=', $endDate)
                            ->whereDate('cuti_end', '>=', $startDate);
                    });
                })
                ->get();

                $absensi = [];

                foreach ($rawAbsensi as $row) {
                    $isCuti = str_contains($row->status ?? '', 'CUTI')
                        && $row->cuti_start && $row->cuti_end;

                    if ($isCuti) {
                        $from = Carbon::parse($row->cuti_start)->max($startDate);
                        $to   = Carbon::parse($row->cuti_end)->min($endDate);

                        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                            $key = $d->format('Y-m-d');

                            if (! isset($absensi[$key])) {
                                $clone = clone $row;
                                $clone->tanggal = $key;
                                $absensi[$key] = [$clone];
                            }
                        }
                    } else {
                        $key = Carbon::parse($row->tanggal)->format('Y-m-d');
                        $absensi[$key][] = $row;
                    }
                }

                // ====== BENTUK DATA JSON PER BULAN & SIMPAN KE tbhs_viewabsensi ======
                foreach ($bulanList as $b) {
                    $tahun = $b['tahun'];
                    $bulan = $b['month']; // 1–12

                    $mapHariStatus = []; // { "01" => "HADIR", "02" => "TELAT", ... }

                    for ($i = 1; $i <= $b['hari']; $i++) {
                        $tanggal = Carbon::createFromDate($tahun, $bulan, $i)->format('Y-m-d');
                        $dataHari = $absensi[$tanggal][0] ?? null;

                        $status = $dataHari->status ?? 'HADIR';

                        if ($status && str_contains($status, ',')) {
                            $status = explode(',', $status)[0];
                            $status = trim($status);
                        }

                        $hariKey = str_pad($i, 2, '0', STR_PAD_LEFT); // "01"
                        $mapHariStatus[$hariKey] = strtoupper($status);
                    }

                    $keteranganJson = json_encode($mapHariStatus, JSON_UNESCAPED_UNICODE);

                    ViewAbsensi::updateOrCreate(
                        [
                            'id_admin' => $user->id_admin,
                            'tahun'    => $tahun,
                            'bulan'    => $bulan,
                        ],
                        [
                            'keterangan' => $keteranganJson,
                        ]
                    );
                }

                // Bulan aktif = item pertama di $bulanList (memang cuma 1)
                $bulanAktif = $bulanList[0];

                // jumlah hari dalam bulan aktif
                $hariDalamBulanAktif = $bulanAktif['hari'];

                // siapkan array per-hari untuk header & status
                $activeDays = []; // setiap elemen: tanggal, label hari, status, remarks, icon, color

                for ($i = 1; $i <= $hariDalamBulanAktif; $i++) {
                    $tanggalCarbon = Carbon::createFromDate($bulanAktif['tahun'], $bulanAktif['month'], $i);
                    $tanggalKey    = $tanggalCarbon->format('Y-m-d');

                    // label hari: MO, TU, WE, ...
                    $dayLabel = strtoupper(substr($tanggalCarbon->format('D'), 0, 2));

                    $dataHari = $absensi[$tanggalKey][0] ?? null;
                    $status   = $dataHari->status  ?? 'HADIR';
                    $remarks  = $dataHari->remarks ?? '-';

                    if ($status && isset($symbolMap[$status])) {
                        $icon  = $symbolMap[$status]['icon'];
                        $color = $symbolMap[$status]['color'];
                    } else {
                        $icon  = 'H'; // hadir normal
                        $color = 'bg-light text-dark';
                    }

                    $activeDays[] = [
                        'tanggal' => $tanggalKey, // "2025-10-01"
                        'label'   => $dayLabel,   // "MO"
                        'status'  => $status,
                        'remarks' => $remarks,
                        'icon'    => $icon,
                        'color'   => $color,
                    ];
                }

                $bulanSekarang  = strtoupper($bulanObj->translatedFormat('F'));
                $tahunSekarang  = $bulanObj->year;
                $selectedBulan  = $bulanObj->format('Y-m');

                $bulanSekarang  = strtoupper($bulanObj->translatedFormat('F'));
                $tahunSekarang  = $bulanObj->year;
                $selectedBulan  = $bulanObj->format('Y-m');

                $data = array_merge($data, [
                    'user'              => $user,
                    'symbolMap'         => $symbolMap,
                    'bulanList'         => $bulanList,
                    'absensi'           => $absensi,
                    'bulanSekarang'     => $bulanSekarang,
                    'tahunSekarang'     => $tahunSekarang,
                    'dropdownMonths'    => $dropdownMonths,
                    'selectedBulan'     => $selectedBulan,
                    'hariDalamBulanAktif' => $hariDalamBulanAktif,
                    'activeDays'        => $activeDays,
                ]);

            return view('pages.formauditor.index', $data);
        }

    public function indexOP(Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $idSitusArray = explode(',', $user->id_situs);
        // $daftarSitus = Daftarsitus::whereIn('id', $idSitusArray)->pluck('nama_situs', 'id')->toArray();
        $isOperator = $user->id_jabatan == 19;

        // ambil daftar situs untuk tab "OP ABSENSI"
        if (in_array('1', $idSitusArray, true)) {
            // id_situs 1 = ALL SITE
            $sites = Daftarsitus::orderBy('nama_situs')->get();
        } else {
            $sites = Daftarsitus::whereIn('id', $idSitusArray)
                ->orderBy('nama_situs')
                ->get();
        }

        $queryBase = Forumaudit::query()->where('soft_delete', '0');

        if ($isOperator) {
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where(function($query) use ($idSitusArray) {
                    foreach ($idSitusArray as $situsId) {
                        $query->orWhereRaw("FIND_IN_SET(?, id_situs)", [$situsId]);
                    }
                })
                ->pluck('id_admin');

            $queryBase->whereIn('created_for', $exceptUsers);
        }

        // Fungsi untuk clone query dan filter
        $countCases = fn ($case, $fault = null, $periode = false) =>
            (clone $queryBase)
                ->when($case, fn($q) => $q->where('status_case', $case))
                ->when($fault !== null, fn($q) =>
                    is_array($fault)
                        ? $q->whereIn('status_kesalahan', $fault)
                        : $q->where('status_kesalahan', $fault)
                )
                ->when($periode, fn($q) => $q->where('periode', $periodeFilter))
                ->count();

        $data = [
            'periodes' => SettingPeriode::all(),
            'case_open' => $countCases('1', '0'),
            'case_progress' => $countCases('2', '0'),
            'case_progress_aman' => $countCases('2', '1'),
            'case_progress_bersalah' => $countCases('2', [2, 3, 4]),
            'case_pending' => $countCases('3', [0, 1, 2, 3, 4]),
            'case_closed_aman' => $countCases('4', '1', true),
            'case_closed_bersalah_low' => $countCases('4', '2', true),
            'case_closed_bersalah_medium' => $countCases('4', '3', true),
            'case_closed_bersalah_high' => $countCases('4', '4', true),
            'sites' => $sites,

        ];

        return view('pages.formauditor.opindex', $data);
    }

    public function opabsensiStaff(Request $request)
    {
        $idSitus = $request->query('id_situs');

        if (empty($idSitus)) {
            return response()->json([]);
        }

        $ids = is_array($idSitus) ? $idSitus : [$idSitus];

        $staff = User::where('status', 1)   // 🔹 HANYA USER AKTIF
            ->where(function ($q) use ($ids) {
                foreach ($ids as $sid) {
                    $sid = trim($sid);
                    if ($sid === '') continue;

                    $q->orWhereRaw('FIND_IN_SET(?, id_situs)', [$sid]);
                }
            })
            ->select('id_admin', 'nama_staff', 'id_situs')
            ->orderBy('id_admin', 'asc')
            ->orderBy('nama_staff', 'asc')
            ->get();

        return response()->json($staff);
    }


    public function opabsensiDetail(Request $request)
    {
        $ids = $request->query('id_situs'); // bisa "4,34" / ["4","34"] / null

        if (empty($ids)) {
            return response()->json([]);
        }

        // Normalisasi ke array
        if (!is_array($ids)) {
            // misal: "4,34" → ["4","34"]
            $ids = explode(',', $ids);
        }
        $ids = array_filter(array_map('trim', $ids));

        if (empty($ids)) {
            return response()->json([]);
        }

        $rows = Absensi::from('tbhs_absensi as a')
            ->leftJoin('tbhs_situs as s', function ($join) {
                // sekarang pakai a.id_situs (varchar: "4,34")
                $join->on(DB::raw('FIND_IN_SET(s.id, a.id_situs)'), '>', DB::raw('0'));
            })
            // filter absensi berdasarkan situs yg dipilih di frontend
            ->where(function ($q) use ($ids) {
                foreach ($ids as $id) {
                    $q->orWhereRaw('FIND_IN_SET(?, a.id_situs) > 0', [$id]);
                }
            })
            ->where('a.soft_delete', 0)  // ⬅️ hanya ambil yang belum dihapus
            ->groupBy(
                'a.id',
                'a.id_admin',
                'a.nama_staff',
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
                a.nama_staff,
                a.tanggal,
                a.status,
                a.remarks,
                a.cuti_start,
                a.cuti_end,
                GROUP_CONCAT(DISTINCT s.nama_situs ORDER BY s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->orderByDesc('a.tanggal')
            ->orderBy('a.id')
            ->get();

        // boleh kembalikan langsung array atau dibungkus data, dua-duanya didukung JS kamu
        return response()->json($rows);
        // atau:
        // return response()->json(['data' => $rows]);
    }

    public function opAbsensiStats()
    {
        $user = auth()->user();

        // id_situs string seperti "3,8,21"
        $idSitusArray = array_filter(array_map('trim', explode(',', (string) $user->id_situs)));
        $today        = now()->toDateString();

        $isAllSite = in_array('1', $idSitusArray, true);

        if ($isAllSite) {
            // ============== ALL SITE ==============
            // 1) TOTAL SITUS: semua situs di tabel tbhs_situs
            $totalSites = Daftarsitus::count();

            // 2) TOTAL STAFF AKTIF: semua user aktif, tanpa filter situs
            $totalStaff = User::where('status', 1)->count();

            // 3) TOTAL ABSENSI HARI INI: semua absensi hari ini (distinct id_admin)
            $todayAbsensi = Absensi::whereDate('tanggal', $today)
                ->distinct('id_admin')
                ->count('id_admin');
        } else {
            // ============== HANYA SITUS YANG DIMILIKI USER ==============
            // 1) TOTAL SITUS: banyaknya id_situs yang dimiliki user
            $totalSites = count($idSitusArray);

            // 2) TOTAL STAFF AKTIF: yang punya salah satu situs ini
            $totalStaff = User::where('status', 1)
                ->where(function ($q) use ($idSitusArray) {
                    foreach ($idSitusArray as $sid) {
                        $q->orWhereRaw('FIND_IN_SET(?, id_situs)', [$sid]);
                    }
                })
                ->count();

            // 3) TOTAL ABSENSI HARI INI: hanya di situs-situs ini
            $todayAbsensi = Absensi::whereDate('tanggal', $today)
                ->where(function ($q) use ($idSitusArray) {
                    foreach ($idSitusArray as $sid) {
                        $q->orWhereRaw('FIND_IN_SET(?, id_situs)', [$sid]);
                    }
                })
                ->distinct('id_admin')
                ->count('id_admin');
        }

        return response()->json([
            'totalSites'   => $totalSites,
            'totalStaff'   => $totalStaff,
            'todayAbsensi' => $todayAbsensi, // kalau di view nggak dipakai juga nggak masalah
        ]);
    }


    public function new(Request $request)
    {
        // Cek akses
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if (!$akses || ($akses[0]->nilai ?? 0) == 0) {
            return view('error');
        }

        // ================== Periode aktif (untuk default selected di dropdown) ==================
        $periodes = SettingPeriode::all();
        $ymToday  = now()->format('Y-m');
        $selectedPeriode = null;

        foreach ($periodes as $p) {
            $from = new \DateTime($p->bulan_dari);
            $to   = new \DateTime($p->bulan_ke);
            $fromYm = $from->format('Y-m');
            $toYm   = $to->format('Y-m');

            if ($ymToday >= $fromYm && $ymToday <= $toYm) {
                $selectedPeriode = $p->tahun . $p->periode; // contoh: 20241
                break;
            }
        }

        // ================== Kartu ringkasan kasus ==================
        // status_case: 1=open, 2=on progress, 3=pending, 4=close
        // status_kesalahan: 0=belum ditentukan, 1=tidak bersalah, 2=low, 3=medium, 4=high
        $tahun = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahun])
            ->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')
            ->value('max_digit');
        $periodeSekarang = $tahun . $maxPeriode;

        $case_open                   = Forumaudit::where('status_case', 1)->where('status_kesalahan', 0)->where('soft_delete','0')->count();
        $case_progress               = Forumaudit::where('status_case', 2)->where('status_kesalahan', 0)->where('soft_delete','0')->count();
        $case_progress_aman          = Forumaudit::where('status_case', 2)->where('status_kesalahan', 1)->where('soft_delete','0')->count();
        $case_progress_bersalah      = Forumaudit::where('status_case', 2)->whereIn('status_kesalahan', [2,3,4])->where('soft_delete','0')->count();
        $case_pending                = Forumaudit::where('status_case', 3)->whereIn('status_kesalahan', [0,1,2,3,4])->where('soft_delete','0')->count();
        $case_closed_aman            = Forumaudit::where('status_case', 4)->where('status_kesalahan', 1)->where('periode', $periodeSekarang)->where('soft_delete','0')->count();
        $case_closed_bersalah_low    = Forumaudit::where('status_case', 4)->where('status_kesalahan', 2)->where('periode', $periodeSekarang)->where('soft_delete','0')->count();
        $case_closed_bersalah_medium = Forumaudit::where('status_case', 4)->where('status_kesalahan', 3)->where('periode', $periodeSekarang)->where('soft_delete','0')->count();
        $case_closed_bersalah_high   = Forumaudit::where('status_case', 4)->where('status_kesalahan', 4)->where('periode', $periodeSekarang)->where('soft_delete','0')->count();

        // ================== Dropdown Staff & Periode ==================
        $datastaffs = User::orderBy('id_admin')->pluck('id_admin', 'id_admin'); // <option value="id">id</option>

        // ================== Dropdown TOPIK (master tbhs_topik) ==================
        $topikList = DB::table('tbhs_topik as t')
            ->when(Schema::hasColumn('tbhs_topik', 'soft_delete'), fn($q) => $q->where('t.soft_delete', '0'))
            ->when(Schema::hasColumn('tbhs_topik', 'status'), fn($q) => $q->where('t.status', 1))
            ->orderBy('t.topik_title')
            ->pluck('t.topik_title')
            ->toArray();

        return view('pages.formauditor.new')->with([
            'periodes'                     => $periodes,
            'selectedPeriode'              => $selectedPeriode,
            'datastaffs'                   => $datastaffs,

            // kartu ringkasan
            'case_open'                    => $case_open,
            'case_progress'                => $case_progress,
            'case_progress_aman'           => $case_progress_aman,
            'case_progress_bersalah'       => $case_progress_bersalah,
            'case_pending'                 => $case_pending,
            'case_closed_aman'             => $case_closed_aman,
            'case_closed_bersalah_low'     => $case_closed_bersalah_low,
            'case_closed_bersalah_medium'  => $case_closed_bersalah_medium,
            'case_closed_bersalah_high'    => $case_closed_bersalah_high,

            // dropdown topik
            'topikList'                    => $topikList,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'case_id'          => 'required|unique:tbhs_forum',
            'topik_title'      => 'required',
            'link_gambar'      => 'required|file|mimes:jpg,jpeg,png,gif|max:2048',
            'topik_deskripsi'  => 'required',
            'created_for'      => 'required',
            'created_for_name' => 'required',
            'created_by'       => 'required',
            'created_by_name'  => 'required',
            'site_situs'       => 'required',
            'status_case'      => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Upload gambar (kalau ada)
        $path = null;
        if ($request->hasFile('link_gambar')) {

            // contoh: "Jakarta Pusat 1" -> "jakarta-pusat-1"
            $siteFolder = Str::slug($request->site_situs);

            // hasil folder: "gambar_topik/jakarta-pusat-1/"
            $location = 'hsforum/'.$siteFolder.'/';

            // upload ke DO Spaces, return PATH saja (bukan URL)
            $path = ImagesUpload::upload($request->file('link_gambar'), $location, 'spaces');
        }

        // Normalisasi status
        $statusCase       = $this->normalizeStatusCase($request->status_case) ?? 1;
        $statusKesalahan  = $this->normalizeStatusKesalahan($request->status_kesalahan) ?? 0;

        $store = Forumaudit::create([
            'slug'              => substr(str_shuffle(md5(time())), 0, 25),
            'case_id'           => $request->case_id,
            'topik_title'       => $request->topik_title,

            // disimpan PATH, contoh: "uploads/gambar_topik/jakarta-pusat-1/xxxxx.jpg"
            'link_gambar'       => $path,

            'topik_deskripsi'   => $request->topik_deskripsi,
            'created_for'       => $request->created_for,
            'created_for_name'  => $request->created_for_name,
            'created_by'        => $request->created_by,
            'created_by_name'   => $request->created_by_name,
            'site_situs'        => $request->site_situs,
            'status_case'       => $statusCase,
            'status_kesalahan'  => $statusKesalahan,
            'periode'           => $request->periode,
        ]);

        return $store
            ? redirect()->route('auditorforum.new')->with('success', 'Data berhasil disimpan')
            : redirect()->back()->with('danger', 'Gagal simpan! Silakan hubungi Administrator');
    }



    public function opencases(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','1')->where('status_kesalahan','0')->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('hsforum.post',$model->slug). '" class="btn btn-warning btn-icon edit">
                                    <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                                </a>
                            ';

                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.open', compact('html'));
    }

    public function opencasesOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $idSitusArray = explode(',', $user->id_situs);
        $daftarSitus = Daftarsitus::whereIn('id', $idSitusArray)->pluck('nama_situs', 'id')->toArray();
        $isOperator = $user->id_jabatan == 19;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '1')
            ->where('status_kesalahan', '0')
            ->where('soft_delete', '0');

        if ($isOperator) {
            // Ambil semua user dengan jabatan tertentu dan situs yang cocok
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where(function ($q) use ($idSitusArray) {
                    foreach ($idSitusArray as $situsId) {
                        $q->orWhereRaw("FIND_IN_SET(?, id_situs)", [$situsId]);
                    }
                })
                ->pluck('id_admin');

            $query->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                    return '
                        <a href="' . route('opforum.post',$model->slug). '" class="btn btn-warning btn-icon edit">
                            <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                        </a>
                    ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.opopen', compact('html'));
    }

    public function onprogresscases(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','2')->where('status_kesalahan','0')->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('hsforum.post',$model->slug). '" class="btn btn-warning btn-icon edit">
                                    <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                                </a>
                            ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.progress', compact('html'));
    }

    public function onprogresscasesOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $idSitusArray = explode(',', $user->id_situs);
        $daftarSitus = Daftarsitus::whereIn('id', $idSitusArray)->pluck('nama_situs', 'id')->toArray();
        $isOperator = $user->id_jabatan == 19;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '2')
            ->where('status_kesalahan', '0')
            ->where('soft_delete', '0');

    if ($isOperator) {
        // Ambil semua user dengan jabatan tertentu dan situs yang cocok
        $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
            ->where(function ($q) use ($idSitusArray) {
                foreach ($idSitusArray as $situsId) {
                    $q->orWhereRaw("FIND_IN_SET(?, id_situs)", [$situsId]);
                }
            })
            ->pluck('id_admin');

        $query->whereIn('created_for', $exceptUsers);
    } else {
        $query->where('created_for', $user->id_admin)
              ->where('created_for_name', $user->nama_staff);
    }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                    return '
                        <a href="' . route('opforum.post',$model->slug). '" class="btn btn-warning btn-icon edit">
                            <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                        </a>
                    ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.opprogress', compact('html'));
    }

    public function onprogressnoguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','2')->where('status_kesalahan','1')->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('hsforum.post',$model->slug). '" class="btn btn-warning btn-icon edit">
                                    <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                                </a>
                            ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.progressnoguilt', compact('html'));
    }

    public function onprogressnoguiltOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $idSitusArray = explode(',', $user->id_situs);
        $daftarSitus = Daftarsitus::whereIn('id', $idSitusArray)->pluck('nama_situs', 'id')->toArray();
        $isOperator = $user->id_jabatan == 19;

        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '2')
            ->where('status_kesalahan', '1')
            ->where('soft_delete', '0');

        if ($isOperator) {
            // Ambil semua user dengan jabatan tertentu dan situs yang cocok
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where(function ($q) use ($idSitusArray) {
                    foreach ($idSitusArray as $situsId) {
                        $q->orWhereRaw("FIND_IN_SET(?, id_situs)", [$situsId]);
                    }
                })
                ->pluck('id_admin');

            $query->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                    return '
                        <a href="' . route('opforum.post',$model->slug). '" class="btn btn-warning btn-icon edit">
                            <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                        </a>
                    ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.opprogressnoguilt', compact('html'));
    }

    public function onprogressguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','2')->whereIn('status_kesalahan',[2,3,4])->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('hsforum.post',$model->slug). '" class="btn btn-warning btn-icon edit">
                                    <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                                </a>
                            ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.progressguilt', compact('html'));
    }

    public function onprogressguiltOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $idSitusArray = explode(',', $user->id_situs);
        $daftarSitus = Daftarsitus::whereIn('id', $idSitusArray)->pluck('nama_situs', 'id')->toArray();
        $isOperator = $user->id_jabatan == 19;

        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '2')
            ->whereIn('status_kesalahan', [2, 3, 4])
            ->where('soft_delete', '0');

        if ($isOperator) {
            // Ambil semua user dengan jabatan tertentu dan situs yang cocok
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where(function ($q) use ($idSitusArray) {
                    foreach ($idSitusArray as $situsId) {
                        $q->orWhereRaw("FIND_IN_SET(?, id_situs)", [$situsId]);
                    }
                })
                ->pluck('id_admin');

            $query->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                    return '
                        <a href="' . route('opforum.post',$model->slug). '" class="btn btn-warning btn-icon edit">
                            <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                        </a>
                    ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.opprogressguilt', compact('html'));
    }

    public function pendingcases(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','3')->whereIn('status_kesalahan', [0,1,2,3,4])->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('hsforum.post',$model->slug). '" class="btn btn-warning btn-icon edit">
                                    <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                                </a>
                            ';

                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.pending', compact('html'));
    }

    public function pendingcasesOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user ->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $idSitusArray = explode(',', $user->id_situs);
        $daftarSitus = Daftarsitus::whereIn('id', $idSitusArray)->pluck('nama_situs', 'id')->toArray();
        $isOperator = $user->id_jabatan == 19;

        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '3')
            ->whereIn('status_kesalahan', [0, 1, 2, 3, 4])
            ->where('soft_delete', '0');

        if ($isOperator) {
            // Ambil semua user dengan jabatan tertentu dan situs yang cocok
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where(function ($q) use ($idSitusArray) {
                    foreach ($idSitusArray as $situsId) {
                        $q->orWhereRaw("FIND_IN_SET(?, id_situs)", [$situsId]);
                    }
                })
                ->pluck('id_admin');

            $query->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                    return '
                        <a href="' . route('opforum.post',$model->slug). '" class="btn btn-warning btn-icon edit">
                            <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                        </a>
                    ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.oppending', compact('html'));
    }

    public function closedcasenoguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','4')->where('status_kesalahan','1')->where('periode', $periodeFilter)->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('hsforum.postdetails',$model->slug). '" class="btn btn-info btn-icon edit">
                                    <i class="fas fa-info-circle mg-r-0"> Details</i>
                                </a>
                            ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.closednoguilt', compact('html'));
    }

        public function closedcasenoguiltOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $idSitusArray = explode(',', $user->id_situs);
        $daftarSitus = Daftarsitus::whereIn('id', $idSitusArray)->pluck('nama_situs', 'id')->toArray();
        $isOperator = $user->id_jabatan == 19;

        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;

        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '4')
            ->where('status_kesalahan', '1')
            ->where('periode', $periodeFilter)
            ->where('soft_delete', '0');

        if ($isOperator) {
            // Ambil semua user dengan jabatan tertentu dan situs yang cocok
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where(function ($q) use ($idSitusArray) {
                    foreach ($idSitusArray as $situsId) {
                        $q->orWhereRaw("FIND_IN_SET(?, id_situs)", [$situsId]);
                    }
                })
                ->pluck('id_admin');

            $query->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                    return '
                        <a href="' . route('opforum.postdetails',$model->slug). '" class="btn btn-info btn-icon edit">
                            <i class="fas fa-info-circle mg-r-0"> Details</i>
                        </a>
                    ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.opclosednoguilt', compact('html'));
    }

    public function closedcaselowguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','4')->where('status_kesalahan','2')->where('periode', $periodeFilter)->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('hsforum.postdetails',$model->slug). '" class="btn btn-info btn-icon edit">
                                    <i class="fas fa-info-circle mg-r-0"> Details</i>
                                </a>
                            ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.closedlowguilt', compact('html'));
    }

    public function closedcaselowguiltOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $idSitusArray = explode(',', $user->id_situs);
        $daftarSitus = Daftarsitus::whereIn('id', $idSitusArray)->pluck('nama_situs', 'id')->toArray();
        $isOperator = $user->id_jabatan == 19;

        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '4')
            ->where('status_kesalahan', '2')
            ->where('periode', $periodeFilter)
            ->where('soft_delete', '0');

        if ($isOperator) {
            // Ambil semua user dengan jabatan tertentu dan situs yang cocok
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where(function ($q) use ($idSitusArray) {
                    foreach ($idSitusArray as $situsId) {
                        $q->orWhereRaw("FIND_IN_SET(?, id_situs)", [$situsId]);
                    }
                })
                ->pluck('id_admin');

            $query->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                    return '
                        <a href="' . route('opforum.postdetails',$model->slug). '" class="btn btn-info btn-icon edit">
                            <i class="fas fa-info-circle mg-r-0"> Details</i>
                        </a>
                    ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.opclosedlowguilt', compact('html'));
    }


    public function closedcasemediumguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','4')->where('status_kesalahan','3')->where('periode', $periodeFilter)->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('hsforum.postdetails',$model->slug). '" class="btn btn-info btn-icon edit">
                                    <i class="fas fa-info-circle mg-r-0"> Details</i>
                                </a>
                            ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.closedmediumguilt', compact('html'));
    }

    public function closedcasemediumguiltOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $idSitusArray = explode(',', $user->id_situs);
        $daftarSitus = Daftarsitus::whereIn('id', $idSitusArray)->pluck('nama_situs', 'id')->toArray();
        $isOperator = $user->id_jabatan == 19;

        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '4')
            ->where('status_kesalahan', '3')
            ->where('periode', $periodeFilter)
            ->where('soft_delete', '0');

        if ($isOperator) {
            // Ambil semua user dengan jabatan tertentu dan situs yang cocok
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where(function ($q) use ($idSitusArray) {
                    foreach ($idSitusArray as $situsId) {
                        $q->orWhereRaw("FIND_IN_SET(?, id_situs)", [$situsId]);
                    }
                })
                ->pluck('id_admin');

            $query->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                    return '
                        <a href="' . route('opforum.postdetails',$model->slug). '" class="btn btn-info btn-icon edit">
                            <i class="fas fa-info-circle mg-r-0"> Details</i>
                        </a>
                    ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.opclosedmediumguilt', compact('html'));
    }

    public function closedcasehighguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','4')->where('status_kesalahan','4')->where('periode', $periodeFilter)->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('hsforum.postdetails',$model->slug). '" class="btn btn-info btn-icon edit">
                                    <i class="fas fa-info-circle mg-r-0"> Details</i>
                                </a>
                            ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.closedhighguilt', compact('html'));
    }

    public function closedcasehighguiltOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $idSitusArray = explode(',', $user->id_situs);
        $daftarSitus = Daftarsitus::whereIn('id', $idSitusArray)->pluck('nama_situs', 'id')->toArray();
        $isOperator = $user->id_jabatan == 19;

        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '4')
            ->where('status_kesalahan', '4')
            ->where('periode', $periodeFilter)
            ->where('soft_delete', '0');

        if ($isOperator) {
            // Ambil semua user dengan jabatan tertentu dan situs yang cocok
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where(function ($q) use ($idSitusArray) {
                    foreach ($idSitusArray as $situsId) {
                        $q->orWhereRaw("FIND_IN_SET(?, id_situs)", [$situsId]);
                    }
                })
                ->pluck('id_admin');

            $query->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                    return '
                        <a href="' . route('opforum.postdetails',$model->slug). '" class="btn btn-info btn-icon edit">
                            <i class="fas fa-info-circle mg-r-0"> Details</i>
                        </a>
                    ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.opclosedhighguilt', compact('html'));
    }

    public function auditoropencases(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('status_case','1')->where('status_kesalahan','0')->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah URL penuh (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        // Coba lewat disk CDN 'spaces' dulu
                        try {
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // Kalau disk 'spaces' belum diset / error → fallback ke storage default
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('auditorforum.auditorpost',$model->slug). '" class="btn btn-warning btn-icon edit">
                                    <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                                </a>
                            ';

                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.auditoropen', compact('html'));
    }

    public function auditoronprogresscases(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('status_case','2')->where('status_kesalahan','0')->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('auditorforum.auditorpost',$model->slug). '" class="btn btn-warning btn-icon edit">
                                    <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                                </a>
                            ';

                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.auditorprogress', compact('html'));
    }

    public function auditoronprogressnoguiltcases(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('status_case','2')->where('status_kesalahan','1')->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('auditorforum.auditorpost',$model->slug). '" class="btn btn-warning btn-icon edit">
                                    <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                                </a>
                            ';

                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.auditorprogressnoguilt', compact('html'));
    }

    public function auditoronprogressguiltcases(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('status_case','2')->whereIn('status_kesalahan',[2,3,4])->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('auditorforum.auditorpost',$model->slug). '" class="btn btn-warning btn-icon edit">
                                    <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                                </a>
                            ';

                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.auditorprogressguilt', compact('html'));
    }

    public function auditoronpendingcases(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('status_case','3')->whereIn('status_kesalahan',[0,1,2,3,4])->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('auditorforum.auditorpost',$model->slug). '" class="btn btn-warning btn-icon edit">
                                    <i class="fas fa-pencil-alt mg-r-0">Comment</i>
                                </a>
                            ';

                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.auditorpending', compact('html'));
    }

    public function auditorclosedcasesnoguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;

        $hasRecoveryAccess = Privilegeaccess::where('id_admin', auth()->user()->id_admin)
        ->where('menu_id', 'HSF015')
        ->exists();

        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('status_case','4')->where('status_kesalahan','1')->where('periode', $periodeFilter)->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) use ($hasRecoveryAccess) {
                    $recoveryButton = '';
                    if ($hasRecoveryAccess) {
                        $recoveryButton = '
                            <button type="button" class="btn btn-warning btn-icon recovery" style="font-size: 14px;"
                                    data-url="' . route('auditorforum.recovery', $model->slug) . '"
                                    data-toggle="modal" data-target="#recoveryModal">
                                <i class="fas fa-undo" style="font-size: 23px;"></i>
                            </button>
                        ';
                    }

                    return '
                        <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <a href="' . route('auditorforum.auditorpostdetails', $model->slug) . '"
                               class="btn btn-info btn-icon edit" style="font-size: 14px;">
                                <i class="fas fa-info-circle" style="font-size: 23px;"></i>
                            </a>
                            ' . $recoveryButton . '
                        </div>
                    ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.auditorclosednoguilt', compact('html'));
    }

    public function auditorclosedcaseslowguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high

        $hasRecoveryAccess = Privilegeaccess::where('id_admin', auth()->user()->id_admin)
        ->where('menu_id', 'HSF015')
        ->exists();

        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('status_case','4')->where('status_kesalahan','2')->where('periode', $periodeFilter)->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) use ($hasRecoveryAccess) {
                    $recoveryButton = '';
                    if ($hasRecoveryAccess) {
                        $recoveryButton = '
                            <button type="button" class="btn btn-warning btn-icon recovery" style="font-size: 14px;"
                                    data-url="' . route('auditorforum.recovery', $model->slug) . '"
                                    data-toggle="modal" data-target="#recoveryModal">
                                <i class="fas fa-undo" style="font-size: 23px;"></i>
                            </button>
                        ';
                    }

                    return '
                        <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <a href="' . route('auditorforum.auditorpostdetails', $model->slug) . '"
                               class="btn btn-info btn-icon edit" style="font-size: 14px;">
                                <i class="fas fa-info-circle" style="font-size: 23px;"></i>
                            </a>
                            ' . $recoveryButton . '
                        </div>
                    ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.auditorclosedlowguilt', compact('html'));
    }

    public function auditorclosedcasesmediumguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high

        $hasRecoveryAccess = Privilegeaccess::where('id_admin', auth()->user()->id_admin)
        ->where('menu_id', 'HSF015')
        ->exists();

        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('status_case','4')->where('status_kesalahan','3')->where('periode', $periodeFilter)->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) use ($hasRecoveryAccess) {
                    $recoveryButton = '';
                    if ($hasRecoveryAccess) {
                        $recoveryButton = '
                            <button type="button" class="btn btn-warning btn-icon recovery" style="font-size: 14px;"
                                    data-url="' . route('auditorforum.recovery', $model->slug) . '"
                                    data-toggle="modal" data-target="#recoveryModal">
                                <i class="fas fa-undo" style="font-size: 23px;"></i>
                            </button>
                        ';
                    }

                    return '
                        <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <a href="' . route('auditorforum.auditorpostdetails', $model->slug) . '"
                               class="btn btn-info btn-icon edit" style="font-size: 14px;">
                                <i class="fas fa-info-circle" style="font-size: 23px;"></i>
                            </a>
                            ' . $recoveryButton . '
                        </div>
                    ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.auditorclosedmediumguilt', compact('html'));
    }

    public function auditorclosedcaseshighguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $tahunSekarang = now()->year;
        $maxPeriode = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high

        $hasRecoveryAccess = Privilegeaccess::where('id_admin', auth()->user()->id_admin)
        ->where('menu_id', 'HSF015')
        ->exists();

        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('status_case','4')->where('status_kesalahan','4')->where('periode', $periodeFilter)->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    return match ($model->status_case) {
                        1 => 'Open',
                        2 => 'On Progress',
                        3 => 'Pending',
                        default => 'Closed',
                    };
                })
                ->addColumn('action', function ($model) use ($hasRecoveryAccess) {
                    $recoveryButton = '';
                    if ($hasRecoveryAccess) {
                        $recoveryButton = '
                            <button type="button" class="btn btn-warning btn-icon recovery" style="font-size: 14px;"
                                    data-url="' . route('auditorforum.recovery', $model->slug) . '"
                                    data-toggle="modal" data-target="#recoveryModal">
                                <i class="fas fa-undo" style="font-size: 23px;"></i>
                            </button>
                        ';
                    }

                    return '
                        <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <a href="' . route('auditorforum.auditorpostdetails', $model->slug) . '"
                               class="btn btn-info btn-icon edit" style="font-size: 14px;">
                                <i class="fas fa-info-circle" style="font-size: 23px;"></i>
                            </a>
                            ' . $recoveryButton . '
                        </div>
                    ';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'status_case', 'class' => 'text-center', 'name' => 'status_case', 'title' => 'Status Topik', 'width' => '180px'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.formauditor.auditorclosedhighguilt', compact('html'));
    }

    public function auditorclosedcasesreport(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $tahunSekarang = now()->year;
        $currentPeriodeRecord = Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->orderBy('periode', 'desc')->first();
        $currentPeriode = $currentPeriodeRecord ? $currentPeriodeRecord->periode : null;
        if (request()->ajax()) {
            $query = Forumaudit::where('status_case', '4')->whereIn('status_kesalahan', [1, 2, 3, 4])->where('periode', '<>', $currentPeriode)->where('soft_delete', '0');

            if ($request->filled('search_keywords')) {
                $keywords = explode(',', $request->search_keywords);

                $query->where(function($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $q->orWhere(function($query) use ($keyword) {
                            $table = (new Forumaudit)->getTable();
                            foreach (Schema::getColumnListing($table) as $column) {
                                $query->orWhereRaw('POSITION(? IN ' . $column . ') > 0', [$keyword]);
                            }
                        });
                    }
                });
            }

            if ($request->filled('site_situs')) {
                $query->where('site_situs', $request->site_situs);
            }

            if ($request->filled('periode')) {
                $tahun = substr($request->periode, 0, 4);
                $periode = substr($request->periode, 4, 1);
                $query->whereRaw('LEFT(periode, 4) = ? AND SUBSTRING(periode, 5, 1) = ?', [$tahun, $periode]);
            }

            if ($request->filled('status_kesalahan')) {
                $query->where('status_kesalahan', $request->status_kesalahan);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    $raw = ltrim((string) $model->link_gambar, '/');

                    if ($raw === '') {
                        return '<span class="text-muted">Tidak ada</span>';
                    }

                    // Kalau sudah full URL (CDN / domain lain)
                    if (Str::startsWith($raw, ['http://', 'https://'])) {
                        $url = $raw;
                    } else {
                        try {
                            // kalau pakai CDN (DigitalOcean Spaces / S3) dengan disk "spaces"
                            $url = Storage::disk('spaces')->url($raw);
                        } catch (\Throwable $e) {
                            // fallback ke storage default Laravel
                            $url = Storage::url($raw);
                        }
                    }

                    return '
                        <img src="'.$url.'"
                            class="img-responsive"
                            style="width:200px; max-height:120px; object-fit:cover"/>
                    ';
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_kesalahan', function ($model) {
                    $status = ["1" => "Tidak Bersalah", "2" => "Bersalah (Low)", "3" => "Bersalah (Medium)", "4" => "Bersalah (High)"];
                    return $status[$model->status_kesalahan] ?? "Unknown";
                })
                ->addColumn('action', function ($model) {
                    return '<a href="' . route('oldperiode.auditorpostdetailsreport', $model->slug) . '" class="btn btn-info btn-icon edit"><i class="fas fa-info-circle mg-r-0"> Details</i></a>';
                })
                ->rawColumns(['action', 'link_gambar'])
                ->toJson();
        }

        $html = $builder->columns([
            [
                'data' => 'DT_RowIndex', 'title' => '#',
                'orderable' => false, 'searchable' => false,
                'width' => '24px'
            ],
            [
                'data' => 'case_id',
                'name' => 'case_id',
                'title' => 'Topik ID',
                'width' => '100px'
            ],
            [
                'data' => 'topik_title',
                'name' => 'topik_title',
                'title' => 'Topik',
                'width' => '250px'
            ],
            [
                'data' => 'site_situs',
                'name' => 'site_situs',
                'title' => 'Situs',
                'width' => '100px'
            ],
            [
                'data' => 'created_for',
                'name' => 'created_for',
                'title' => 'ID Staff',
                'width' => '100px'
            ],
            [
                'data' => 'link_gambar',
                'name' => 'link_gambar',
                'title' => 'Gambaran Topik',
                'width' => '200px'
            ],
            [
                'data' => 'created_at', 'class' => 'text-center',
                'name' => 'created_at',
                'title' => 'Tanggal Topik',
                'width' => '180px'
            ],
            [
                'data' => 'periode', 'class' => 'text-center',
                'name' => 'periode',
                'title' => 'Periode Topik',
                'width' => '180px'
            ],
            [
                'data' => 'status_kesalahan', 'class' => 'text-center',
                'name' => 'status_kesalahan',
                'title' => 'Status Kesalahan',
                'width' => '180px'
            ],
            [
                'data' => 'created_by', 'class' => 'text-center',
                'name' => 'created_by',
                'title' => 'ID Auditor',
                'width' => '100px'
            ],
            [
                'data' => 'action', 'title' => 'Action', 'class' => 'text-center',
                'orderable' => false, 'searchable' => false,
                'width' => '120px'
            ]
        ])->parameters([
            'searching' => false,
            'paging' => true,
            'info' => true,
            'lengthMenu' => [[10, 25, 50, 100, 250, 500, -1], [10, 25, 50, 100, 250, 500, "Show all"]],
            'pageLength' => 10,
        ]);

        return view('pages.reporting.auditorclosedcasesreport', compact('html'));
    }

    /**
     * Show the form for editing the specified resource.
     */

public function post(string $slug)
{
    $dataforumaudit = Forumaudit::where('slug', $slug)->firstOrFail();

    $dataforumauditpost = Forumauditpost::where('slug', $dataforumaudit->slug)
        ->where('parent_forum_id', $dataforumaudit->id)
        ->where('parent_case_id', $dataforumaudit->case_id)
        ->orderBy('created_at', 'asc')
        ->get();

    $canUpdateStatus = Privilegeaccess::where('id_admin', auth()->user()->id_admin)
        ->where('menu_id', 'HSF008')
        ->exists();

    $periodes = SettingPeriode::orderBy('tahun')->orderBy('periode')->get();

    $periodeOptions = $periodes->map(function ($p) use ($dataforumaudit) {
        $value = $p->tahun . $p->periode;
        return (object) [
            'value'    => $value,
            'label'    => $p->bulan_dari . ' - ' . $p->bulan_ke,
            'selected' => $dataforumaudit->periode === $value,
        ];
    });

    $imgUrl = $dataforumaudit->link_gambar ? Storage::url($dataforumaudit->link_gambar) : '';

    return view('pages.formauditor.posting', [
        'dataforumaudit'     => $dataforumaudit,
        'dataforumauditpost' => $dataforumauditpost,
        'canUpdateStatus'    => $canUpdateStatus,
        'imgUrl'             => $imgUrl,
        'periodeOptions'     => $periodeOptions,
    ]);
}


public function postOP($slug, Request $request)
{
    $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
    if ($akses[0]->nilai == 0) {
        return view('error');
    }

    // ambil daftar periode
    $periodes = SettingPeriode::orderBy('tahun')->orderBy('periode')->get();

    $dataforumaudit = Forumaudit::where('slug', $slug)->firstOrFail();

    $dataforumauditpost = Forumauditpost::where('slug', $dataforumaudit->slug)
        ->where('parent_forum_id', $dataforumaudit->id)
        ->where('parent_case_id', $dataforumaudit->case_id)
        ->orderBy('id', 'ASC')
        ->get();

    // ⇩ Pindahan dari Blade
    $canUpdateStatus = Privilegeaccess::where('id_admin', $request->user()->id_admin)
        ->where('menu_id', 'HSF008')
        ->exists();

    // 🔹 BANGUN $periodeOptions (sama pola dengan comments())
    $periodeOptions = $periodes->map(function ($p) use ($dataforumaudit) {
        $value = $p->tahun . $p->periode; // contoh: 20241

        return (object) [
            'value'    => $value,
            'label'    => $p->bulan_dari . ' - ' . $p->bulan_ke,
            'selected' => $dataforumaudit->periode === $value,
        ];
    });

    // === BIKIN URL GAMBAR YANG ROBUST + CDN ===
    $imgUrl = '';
    $raw = ltrim((string) ($dataforumaudit->link_gambar ?? ''), '/');

    if ($raw !== '') {
        if (Str::startsWith($raw, ['http://', 'https://'])) {
            // Kalau sudah full URL (misal sudah CDN), pakai apa adanya
            $imgUrl = $raw;
        } else {
            // Kalau cuma path file, generate URL dari disk 'spaces' (CDN)
            $imgUrl = Storage::disk('spaces')->url($raw);
        }
    }

    return view('pages.formauditor.opposting')->with([
        'dataforumaudit'     => $dataforumaudit,
        'dataforumauditpost' => $dataforumauditpost,
        'periodes'           => $periodes,        // boleh dipakai kalau masih dibutuhkan
        'periodeOptions'     => $periodeOptions,  // ✅ ini yang dipakai Blade baru
        'canUpdateStatus'    => $canUpdateStatus,
        'imgUrl'             => $imgUrl,
    ]);
}


    /**
     * Show the form for editing the specified resource.
     */
public function auditorpost($slug, Request $request)
{
    $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
    if ((int) data_get($akses, '0.nilai', 0) === 0) {
        return view('error');
    }

    // ➕ Cek privilege HSF008 sama seperti di post() & postOP()
    $canUpdateStatus = Privilegeaccess::where('id_admin', $request->user()->id_admin)
        ->where('menu_id', 'HSF008')
        ->exists();

    $datadetailforumaudit = Forumaudit::where('slug', $slug)->firstOrFail();

    $datadetailforumauditpost = Forumauditpost::where('slug', $datadetailforumaudit->slug)
        ->where('parent_forum_id', $datadetailforumaudit->id)
        ->where('parent_case_id', $datadetailforumaudit->case_id)
        ->orderBy('id','ASC')
        ->get();

    // 🔹 ambil daftar periode
    $periodes = SettingPeriode::orderBy('tahun')->orderBy('periode')->get();

    // 🔹 bangun $periodeOptions (menggunakan periode milik forum ini)
    $periodeOptions = $periodes->map(function ($p) use ($datadetailforumaudit) {
        $value = $p->tahun . $p->periode;

        return (object) [
            'value'    => $value,
            'label'    => $p->bulan_dari . ' - ' . $p->bulan_ke,
            'selected' => $datadetailforumaudit->periode === $value,
        ];
    });

    // === URL gambar siap pakai (lokal / CDN) ===
    $imageUrl = '';
    $raw = ltrim((string) ($datadetailforumaudit->link_gambar ?? ''), '/');

    if ($raw !== '') {
        if (Str::startsWith($raw, ['http://', 'https://'])) {
            // Kalau sudah full URL (mis: CDN), pakai langsung
            $imageUrl = $raw;
        } else {
            // Kalau cuma simpan path file (mis: upload ke Spaces)
            $imageUrl = Storage::disk('spaces')->url($raw);
        }
    }

    return view('pages.formauditor.auditorposting')->with([
        'datadetailforumaudit'     => $datadetailforumaudit,
        'datadetailforumauditpost' => $datadetailforumauditpost,
        'imageUrl'                 => $imageUrl,
        'imgUrl'                   => $imageUrl,
        'canUpdateStatus'          => $canUpdateStatus,
        'periodes'                 => $periodes,        // optional
        'periodeOptions'           => $periodeOptions,  // ✅ penting
    ]);
}


    private function normalizeStatusCase($val): ?int
    {
        if ($val === null || $val === '') return null;
        if (is_numeric($val)) return (int) $val;

        $map = [
            'open'        => 1,
            'on progress' => 2,
            'pending'     => 3,
            'closed'      => 4,
            'close'       => 4,
        ];
        return $map[strtolower(trim($val))] ?? null;
    }

    private function normalizeStatusKesalahan($val): ?int
    {
        if ($val === null || $val === '') return null;
        if (is_numeric($val)) return (int) $val;

        $map = [
            'tidak bersalah' => 1,
            'low'            => 2,
            'medium'         => 3,
            'high'           => 4,
            'aman'           => 1,  // kalau UI pakai label ini
            'bersalah low'   => 2,
            'bersalah medium'=> 3,
            'bersalah high'  => 4,
        ];
        return $map[strtolower(trim($val))] ?? null;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make( $input, [
            'deskripsi_post' => 'required'
        ],
        [
            'deskripsi_post.required'=> 'Komentar Topik Tidak Boleh Kosong!!', // custom message
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $forumaudit = Forumaudit::where('slug',$request->slug)->first();

        // status_case
        if ($request->filled('status_case')) {
            $normalized = $this->normalizeStatusCase($request->status_case);
            if ($normalized !== null) {
                $forumaudit->status_case = $normalized;
            }
        } else {
            // pakai perbandingan yang benar
            if ($forumaudit->status_case == 1) {
                $forumaudit->status_case = 2;
            }
            // else biarkan nilainya
        }

        // status_kesalahan
        if ($request->filled('status_kesalahan')) {
            $normalized = $this->normalizeStatusKesalahan($request->status_kesalahan);
            if ($normalized !== null) {
                $forumaudit->status_kesalahan = $normalized;
            }
        }

        // Update periode (tetap)
        if (!empty($request->periode)) {
            $forumaudit->periode = $request->periode;
        }

        $store = $forumaudit->save();

        Forumauditpost::firstOrCreate([
            'slug' => request('slug'),
            'parent_forum_id' => request('forum_id'),
            'parent_case_id' => request('case_id'),
            'deskripsi_post' => request('deskripsi_post'),
            'updated_by' => auth()->user()->id_admin,
            'updated_by_name' => auth()->user()->nama_staff
        ]);

        return $store
            ? redirect()->back()->with('success','Data berhasil disimpan')
            : redirect()->back()->with('danger','Gagal update! Silakan hubungi Administrator');

    }

    /**
     * Show the details.
     */
    public function postdetails($slug, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $datadetailforumaudit = Forumaudit::where('slug', $slug)->firstOrFail();

        $datadetailforumauditpost = Forumauditpost::where('slug', $datadetailforumaudit->slug)
            ->where('parent_forum_id', $datadetailforumaudit->id)
            ->where('parent_case_id', $datadetailforumaudit->case_id)
            ->orderBy('id','ASC')
            ->get();

        // ==== URL gambar (CDN / storage lokal) ====
        $imageUrl = '';
        $raw = ltrim((string) ($datadetailforumaudit->link_gambar ?? ''), '/');

        if ($raw !== '') {
            if (Str::startsWith($raw, ['http://', 'https://'])) {
                // sudah full URL
                $imageUrl = $raw;
            } elseif (Str::startsWith($raw, 'storage/')) {
                // path lama: storage/...
                $imageUrl = '/'.$raw;
            } else {
                // path file di disk (mis Spaces)
                $raw = preg_replace('#^public/#', '', $raw);
                try {
                    $imageUrl = Storage::disk('spaces')->url($raw); // CDN
                } catch (\Throwable $e) {
                    $imageUrl = Storage::url($raw); // fallback /storage/...
                }
            }
        }

        return view('pages.formauditor.postdetails')->with([
            "datadetailforumaudit"     => $datadetailforumaudit,
            "datadetailforumauditpost" => $datadetailforumauditpost,
            "imageUrl"                 => $imageUrl,
        ]);
    }


    /**
     * Show the details.
     */
    public function auditorpostdetails($slug, Request $request)
    {
        // Cek akses
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ((int) data_get($akses, '0.nilai', 0) === 0) {
            return view('error');
        }

        // Detail & komentar
        $detail = Forumaudit::where('slug', $slug)->firstOrFail();
        $posts  = Forumauditpost::where('slug', $detail->slug)
            ->where('parent_forum_id', $detail->id)
            ->where('parent_case_id', $detail->case_id)
            ->orderBy('id', 'ASC')
            ->get();

        // Status: close / boleh komentar
        $statusCodeRaw = (int) ($detail->status_case ?? 0);
        $statusTextRaw = strtolower(trim((string) ($detail->status_text ?? '')));
        $isClosed      = $statusCodeRaw === 4 || in_array($statusTextRaw, ['close','closed','topik close'], true);
        $canComment    = !$isClosed && $request->boolean('comment', true);

        // Normalisasi label status
        $statusKesalahanMap = [1=>'Tidak Bersalah', 2=>'Bersalah (Low)', 3=>'Bersalah (Medium)', 4=>'Bersalah (High)'];
        $statusCaseMap      = [1=>'Open', 2=>'On Progress', 3=>'Pending Topik', 4=>'Topik Close'];

        $statusKesalahanText = $statusKesalahanMap[(int) ($detail->status_kesalahan ?? 0)]
            ?? (string) ($detail->status_kesalahan ?? '-');
        $statusCaseText = $statusCaseMap[(int) ($detail->status_case ?? 0)]
            ?? (string) ($detail->status_case ?? '-');

        // Siapkan URL gambar (robust + CDN)
        $imageUrl = '';
        $raw = ltrim((string) ($detail->link_gambar ?? ''), '/');

        if ($raw !== '') {
            if (Str::startsWith($raw, ['http://', 'https://'])) {
                // Sudah full URL (bisa CDN lama atau link eksternal)
                $imageUrl = $raw;

            } elseif (Str::startsWith($raw, 'storage/')) {
                // Data lama yang sudah disimpan sebagai "/storage/...."
                $imageUrl = '/' . $raw;

            } else {
                // Data baru: hanya path file (misal "hsforum/xxx.jpg")
                // -> arahkan ke CDN (disk "spaces")
                $raw = preg_replace('#^public/#', '', $raw);

                try {
                    // kalau disk "spaces" ada
                    $imageUrl = Storage::disk('spaces')->url($raw);
                } catch (\Throwable $e) {
                    // fallback ke storage lokal kalau spaces tidak ada / error
                    $imageUrl = Storage::url($raw); // /storage/...
                }
            }
        }

        // Kemasan komentar untuk view (tanggal sudah diformat)
        $comments = $posts->map(function ($p) {
            return [
                'updated_by'     => $p->updated_by,
                'created_at_hms' => optional($p->created_at)->format('Y-m-d H:i:s'),
                'deskripsi_post' => $p->deskripsi_post,
            ];
        });

        return view('pages.formauditor.auditorpostdetails', [
            'datadetailforumaudit' => $detail,
            'comments'             => $comments,
            'canComment'           => $canComment,
            'statusKesalahanText'  => $statusKesalahanText,
            'statusCaseText'       => $statusCaseText,
            'imageUrl'             => $imageUrl,
        ]);
    }

    /**
     * Show the details.
     */
    public function postdetailsOP($slug, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $datadetailforumaudit = Forumaudit::where('slug', $slug)->firstOrFail();

        $datadetailforumauditpost = Forumauditpost::where('slug', $datadetailforumaudit->slug)
            ->where('parent_forum_id', $datadetailforumaudit->id)
            ->where('parent_case_id', $datadetailforumaudit->case_id)
            ->orderBy('id','ASC')
            ->get();

        $imageUrl = '';
        $raw = ltrim((string) ($datadetailforumaudit->link_gambar ?? ''), '/');

        if ($raw !== '') {
            if (Str::startsWith($raw, ['http://', 'https://'])) {
                $imageUrl = $raw;
            } elseif (Str::startsWith($raw, 'storage/')) {
                $imageUrl = '/'.$raw;
            } else {
                $raw = preg_replace('#^public/#', '', $raw);
                try {
                    $imageUrl = Storage::disk('spaces')->url($raw);
                } catch (\Throwable $e) {
                    $imageUrl = Storage::url($raw);
                }
            }
        }

        return view('pages.formauditor.oppostdetails')->with([
            "datadetailforumaudit"     => $datadetailforumaudit,
            "datadetailforumauditpost" => $datadetailforumauditpost,
            "imageUrl"                 => $imageUrl,
        ]);
    }
}

