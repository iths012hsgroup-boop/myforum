<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\AuthLink;
use App\Models\Absensi;
use App\Models\Daftarsitus;
use Carbon\Carbon;

class HrdGrafikController extends Controller
{
    /** ----------------------------------------------------------------
     *  Konstanta & Konstruktor
     *  ---------------------------------------------------------------*/
    private const STATUSES = ['TELAT', 'SAKIT', 'IZIN', 'TANPA KABAR', 'CUTI'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    /** ----------------------------------------------------------------
     *  Helpers Umum
     *  ---------------------------------------------------------------*/

    /**
     * Cek akses HRD untuk user yang login.
     * Return view('error') jika tidak berhak; null jika OK.
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
     * Tentukan cakupan site user: [namaSitusLogin, idSitusArray, isAllSite, user]
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
     * Terapkan filter site bila user tidak ALL SITE.
     */
    private function applySiteFilter($query, array $ids, bool $isAllSite, string $column = 'id_situs')
    {
        return $query->when(!$isAllSite && !empty($ids), function ($q) use ($ids, $column) {
            $q->where(function ($qq) use ($ids, $column) {
                foreach ($ids as $id) {
                    $qq->orWhereRaw("FIND_IN_SET(?, {$column})", [trim($id)]);
                }
            });
        });
    }

    /**
     * Nama bulan (1-12).
     */
    private function monthNames(): array
    {
        return [
            null,
            'Januari','Februari','Maret','April','Mei','Juni',
            'Juli','Agustus','September','Oktober','November','Desember',
        ];
    }

    /**
     * SQL agregasi status (telat/sakit/izin/tanpa_kabar/cuti).
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
     * Ubah periodeKe (1/2) menjadi rentang bulan [startMonth, endMonth].
     */
    private function periodeRangeByHalf(int $periodeKe): array
    {
        return $periodeKe === 1 ? [1, 6] : [7, 12];
    }

    /**
     * Tentukan periode (1/2) default dari bulan berjalan.
     */
    private function defaultPeriodeKeFromMonth(int $month): int
    {
        return $month <= 6 ? 1 : 2;
    }

    /** ----------------------------------------------------------------
     *  Halaman Grafik (Tab Grafik & Compare)
     *  ---------------------------------------------------------------*/

    // route('hrdmanagement.grafik')  GET /hrdmanagement/grafik
    public function index(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) return $resp;

        [$namaSitusLogin, $idSitusArray, $isAllSite] = $this->resolveUserSiteScope();

        $now                = Carbon::now();
        $year               = $now->year;
        $currentMonth       = $now->month;
        $currentMonthNumber = $currentMonth;
        $allMonths          = $this->monthNames();

        // daftar tahun yang tersedia (untuk dropdown compare)
        $yearQuery = Absensi::selectRaw('DISTINCT YEAR(tanggal) as tahun')
            ->where('soft_delete', 0);
        $yearQuery   = $this->applySiteFilter($yearQuery, $idSitusArray, $isAllSite, 'id_situs');
        $compareYears = $yearQuery->orderBy('tahun', 'desc')->pluck('tahun')->toArray();
        $compareYear  = (int) $request->query('compare_year', $year);

        // data tab Grafik (utama) + tab Compare
        $mainData    = $this->buildMainGrafikData($request, $year, $currentMonth, $allMonths, $idSitusArray, $isAllSite);
        $compareData = $this->buildPerbandinganData($compareYear, $allMonths, $idSitusArray, $isAllSite);

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

    /** Kumpulan builder data untuk TAB "GRAFIK" */
    private function buildMainGrafikData(
        Request $request, int $year, int $currentMonth, array $allMonths, array $idSitusArray, bool $isAllSite
    ): array {
        // Periode untuk bar 6 bulan
        $defaultPeriodeKe        = $this->defaultPeriodeKeFromMonth($currentMonth);
        $periodeKe               = (int) $request->query('periode', $defaultPeriodeKe);
        [$startMonth, $endMonth] = $this->periodeRangeByHalf($periodeKe);

        // Bar agregat 6 bulan
        [$labels, $bulanNumbers, $dataTelat, $dataSakit, $dataIzin, $dataTanpaKabar, $dataCuti]
            = $this->getPeriodeBarAggregates($year, $startMonth, $endMonth, $allMonths, $idSitusArray, $isAllSite);

        // Agregat bulan berjalan (5 batang)
        $bulanSekarangLabel = $allMonths[$currentMonth];
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

        // Agregat hari ini (5 batang horizontal)
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

        // Pie per site (6 bulan) — periode bisa beda dari bar 6 bulan
        $defaultDiagramPeriode   = $this->defaultPeriodeKeFromMonth($currentMonth);
        $diagramPeriodeKe        = (int) $request->query('diagram_periode', $defaultDiagramPeriode);
        [$startDiagram, $endDiagram] = $this->periodeRangeByHalf($diagramPeriodeKe);

        [$diagramLabels, $diagramTotals, $diagramDetail]
            = $this->getPeriodePieAggregates($year, $startDiagram, $endDiagram, $idSitusArray, $isAllSite);

        return compact(
            'labels','bulanNumbers','dataTelat','dataSakit','dataIzin','dataTanpaKabar','dataCuti',
            'bulanSekarangLabel','bulanTelat','bulanSakit','bulanIzin','bulanTanpaKabar','bulanCuti',
            'todayLabel','dailyTelat','dailySakit','dailyIzin','dailyTanpaKabar','dailyCuti',
            'diagramLabels','diagramTotals','diagramDetail','periodeKe','diagramPeriodeKe'
        );
    }

    /** Kumpulan builder data untuk TAB "COMPARE" (Periode 1 vs 2) */
    private function buildPerbandinganData(int $year, array $allMonths, array $idSitusArray, bool $isAllSite): array
    {
        $build = function (int $startMonth, int $endMonth) use ($year, $allMonths, $idSitusArray, $isAllSite) {
            [$labels, $bulanNumbers, $telat, $sakit, $izin, $tanpa, $cuti]
                = $this->getPeriodeBarAggregates($year, $startMonth, $endMonth, $allMonths, $idSitusArray, $isAllSite);

            [$diagramLabels, $diagramTotals, $diagramDetail]
                = $this->getPeriodePieAggregates($year, $startMonth, $endMonth, $idSitusArray, $isAllSite);

            return compact('labels','bulanNumbers','telat','sakit','izin','tanpa','cuti','diagramLabels','diagramTotals','diagramDetail');
        };

        $p1 = $build(1, 6);
        $p2 = $build(7, 12);

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

    /** ----------------------------------------------------------------
     *  Query Builder Agregat
     *  ---------------------------------------------------------------*/

    /**
     * Data bar per bulan dalam rentang 6 bulan.
     * Return: [labels, bulanNumbers, telat[], sakit[], izin[], tanpaKabar[], cuti[]]
     */
    private function getPeriodeBarAggregates(
        int $year, int $startMonth, int $endMonth, array $allMonths, array $idSitusArray, bool $isAllSite
    ): array {
        // siapkan label & indeks bulan
        $labels = []; $bulanMap = []; $bulanNumbers = []; $i = 0;
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
        $dataTanpa      = array_fill(0, $len, 0);
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
            if (!isset($bulanMap[$row->bulan])) continue;
            $idx = $bulanMap[$row->bulan];

            $dataTelat[$idx] = (int) $row->telat;
            $dataSakit[$idx] = (int) $row->sakit;
            $dataIzin[$idx]  = (int) $row->izin;
            $dataTanpa[$idx] = (int) $row->tanpa_kabar;
            $dataCuti[$idx]  = (int) $row->cuti;
        }

        return [$labels, $bulanNumbers, $dataTelat, $dataSakit, $dataIzin, $dataTanpa, $dataCuti];
    }

    /**
     * Data pie per situs pada rentang 6 bulan.
     * Return: [labels[], totals[], detail[label] => {...}]
     */
    private function getPeriodePieAggregates(
        int $year, int $startMonth, int $endMonth, array $idSitusArray, bool $isAllSite
    ): array {
        $q = Absensi::from('tbhs_absensi as a')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, a.id_situs)'), '>', DB::raw('0'));
            })
            ->selectRaw("s.id as situs_id, s.nama_situs, {$this->statusSumSelectRaw('a')}")
            ->whereYear('a.tanggal', $year)
            ->whereBetween(DB::raw('MONTH(a.tanggal)'), [$startMonth, $endMonth])
            ->where('a.soft_delete', 0);

        $q = $this->applySiteFilter($q, $idSitusArray, $isAllSite, 'a.id_situs');

        $rows = $q->groupBy('situs_id', 's.nama_situs')->orderBy('s.nama_situs')->get();

        $labels = []; $totals = []; $detail = [];
        foreach ($rows as $row) {
            $nama        = $row->nama_situs;
            $telat       = (int) $row->telat;
            $sakit       = (int) $row->sakit;
            $izin        = (int) $row->izin;
            $tanpaKabar  = (int) $row->tanpa_kabar;
            $cuti        = (int) $row->cuti;

            $labels[] = $nama;
            $totals[] = $telat + $sakit + $izin + $tanpaKabar + $cuti;

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

    /** ----------------------------------------------------------------
     *  Endpoints Detail (AJAX)
     *  ---------------------------------------------------------------*/

    // route('hrdmanagement.grafik.detail')  GET /hrdmanagement/grafik/detail-absensi
    public function detailAbsensi(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) return $resp;

        $status = $request->get('status');
        $bulan  = (int) $request->get('bulan');
        $year   = now()->year;

        [, $idSitusArray, $isAllSite] = $this->resolveUserSiteScope();

        $q = Absensi::from('tbhs_absensi as a')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, a.id_situs)'), '>', DB::raw('0'));
            })
            ->whereYear('a.tanggal', $year)
            ->whereMonth('a.tanggal', $bulan)
            ->where('a.soft_delete', 0);

        if ($status) {
            $q->where('a.status', 'LIKE', "%{$status}%");
        }

        $q = $this->applySiteFilter($q, $idSitusArray, $isAllSite, 'a.id_situs');

        $rows = $q->groupBy(
                'a.id','a.id_admin','a.nama_staff','a.id_situs','a.tanggal','a.status','a.remarks','a.cuti_start','a.cuti_end'
            )
            ->selectRaw('
                a.id, a.id_admin, a.nama_staff, a.id_situs, a.tanggal, a.status, a.remarks, a.cuti_start, a.cuti_end,
                GROUP_CONCAT(s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->orderBy('a.tanggal', 'desc')
            ->get();

        return response()->json(['data' => $rows]);
    }

    // route('hrdmanagement.grafik.diagram_detail') GET /hrdmanagement/grafik/detail-situs
    public function diagramDetail(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) return $resp;

        $namaSitus = $request->get('situs');
        $year      = now()->year;

        [, $idSitusArray, $isAllSite] = $this->resolveUserSiteScope();

        $currentMonth = Carbon::now()->month;
        [$startMonth, $endMonth] = $this->defaultPeriodeKeFromMonth($currentMonth) === 1 ? [1, 6] : [7, 12];

        $q = Absensi::from('tbhs_absensi as a')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, a.id_situs)'), '>', DB::raw('0'));
            })
            ->whereYear('a.tanggal', $year)
            ->whereBetween(DB::raw('MONTH(a.tanggal)'), [$startMonth, $endMonth])
            ->where('a.soft_delete', 0);

        if ($namaSitus) {
            $q->where('s.nama_situs', $namaSitus);
        }

        $q = $this->applySiteFilter($q, $idSitusArray, $isAllSite, 'a.id_situs');

        $rows = $q->groupBy(
                'a.id','a.id_admin','a.nama_staff','a.tanggal','a.status','a.remarks','a.cuti_start','a.cuti_end'
            )
            ->selectRaw('
                a.id, a.id_admin, a.nama_staff, a.tanggal, a.status, a.remarks, a.cuti_start, a.cuti_end,
                GROUP_CONCAT(s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->orderBy('a.tanggal', 'desc')
            ->get();

        return response()->json(['data' => $rows]);
    }

    // route('hrdmanagement.grafik.daily_detail') GET /hrdmanagement/grafik/daily-detail
    public function dailyDetail(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) return $resp;

        $status = $request->get('status');
        $today  = Carbon::today();

        [, $idSitusArray, $isAllSite] = $this->resolveUserSiteScope();

        $q = Absensi::from('tbhs_absensi as a')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, a.id_situs)'), '>', DB::raw('0'));
            })
            ->whereDate('a.tanggal', $today)
            ->where('a.soft_delete', 0);

        if ($status) {
            $q->where('a.status', 'LIKE', "%{$status}%");
        }

        $q = $this->applySiteFilter($q, $idSitusArray, $isAllSite, 'a.id_situs');

        $rows = $q->groupBy(
                'a.id','a.id_admin','a.nama_staff','a.tanggal','a.status','a.remarks','a.cuti_start','a.cuti_end'
            )
            ->selectRaw('
                a.id, a.id_admin, a.nama_staff, a.tanggal, a.status, a.remarks, a.cuti_start, a.cuti_end,
                GROUP_CONCAT(s.nama_situs SEPARATOR ", ") AS nama_situs
            ')
            ->orderBy('a.tanggal', 'desc')
            ->get();

        return response()->json(['data' => $rows]);
    }

    // route('hrdmanagement.grafik.compare_data') GET /hrdmanagement/grafik/compare-data
    public function compareData(Request $request)
    {
        if ($resp = $this->ensureHrdAccess()) return $resp;

        [, $idSitusArray, $isAllSite] = $this->resolveUserSiteScope();

        $year      = (int) $request->query('year', now()->year);
        $allMonths = $this->monthNames();

        $compareData = $this->buildPerbandinganData($year, $allMonths, $idSitusArray, $isAllSite);
        return response()->json($compareData);
    }
}
