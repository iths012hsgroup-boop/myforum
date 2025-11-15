<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AbsensiReportExport;
use App\Models\SettingPeriode;
use App\Helpers\AuthLink;
use App\Models\Daftarsitus;
use App\Models\AbsensiReport;
use Carbon\Carbon;

class HrdReportingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ---------------- Helpers kecil (disalin biar mandiri) --------------- */

    private function ensureHrdAccess()
    {
        $user  = Auth::user();
        $akses = AuthLink::access_url($user->id_admin, 'hrdmanagement');
        if (empty($akses) || $akses[0]->nilai == 0) {
            return view('error');
        }
        return null;
    }

    private function normalizeIdList($raw): array
    {
        if (is_array($raw)) return array_filter($raw);
        if (!empty($raw))   return array_filter(array_map('trim', explode(',', $raw)));
        return [];
    }

    /** Parse "YYYY-1|2" -> [ok, tahun, periodeKe, start, end] */
private function parsePeriodeString(string $periode): array
{
    $parts = explode('-', $periode);
    if (count($parts) !== 2) {
        return [false, 0, 0, null, null];
    }

    $tahun     = (int) $parts[0];
    $periodeKe = (int) $parts[1];

    $setting = SettingPeriode::where('tahun', $tahun)
        ->where('periode', $periodeKe)
        ->first();

    if (!$setting) {
        // periode tidak terdaftar
        return [false, $tahun, $periodeKe, null, null];
    }

    try {
        if (is_numeric($setting->bulan_dari) && is_numeric($setting->bulan_ke)) {
            // CASE: data bulan berupa angka 1–12
            $start = Carbon::create($tahun, (int) $setting->bulan_dari, 1)
                ->startOfMonth()->toDateString();
            $end   = Carbon::create($tahun, (int) $setting->bulan_ke, 1)
                ->endOfMonth()->toDateString();
        } else {
            // CASE: data bulan berupa teks, mis: "Jan", "Januari", dsb.
            $start = Carbon::parse($setting->bulan_dari . ' ' . $tahun)
                ->startOfMonth()->toDateString();
            $end   = Carbon::parse($setting->bulan_ke . ' ' . $tahun)
                ->endOfMonth()->toDateString();
        }
    } catch (\Throwable $e) {
        // kalau parsing bulan gagal, anggap periode tidak valid
        return [false, $tahun, $periodeKe, null, null];
    }

    return [true, $tahun, $periodeKe, $start, $end];
}

    /* ------------------------- Pages & APIs ------------------------------- */

    // GET /hrdmanagement/report-absensi
public function reportingAbsensi()
{
    if ($resp = $this->ensureHrdAccess()) return $resp;

    $sites    = Daftarsitus::select('id', 'nama_situs')->orderBy('nama_situs')->get();
    $periodes = SettingPeriode::orderBy('tahun')->orderBy('periode')->get();

    return view('pages.hrdmanagement.reportingabsensi', compact('sites', 'periodes'));
}

    // GET /hrdmanagement/report-absensi/data
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
            ->when($periode, fn($q) => $q->where('r.periode', $periode))
            ->when(!$periode && $tahun, fn($q) => $q->where('r.periode', 'like', $tahun . '-%'))
            ->groupBy('r.id','r.id_admin','r.nama_staff','r.id_situs','r.periode','r.sakit','r.izin','r.telat','r.tanpa_kabar','r.cuti','r.total_absensi')
            ->selectRaw("
                r.id, r.id_admin, r.nama_staff, r.id_situs,
                GROUP_CONCAT(DISTINCT s.nama_situs ORDER BY s.nama_situs SEPARATOR ', ') AS nama_situs,
                r.periode, r.sakit, r.izin, r.telat, r.tanpa_kabar, r.cuti, r.total_absensi
            ")
            ->orderByDesc('r.periode')
            ->get();

        return response()->json(['data' => $rows]);
    }

    // POST /hrdmanagement/report-absensi/generate
    public function generateAbsensiReport(Request $request)
    {
        $request->validate(['periode' => 'required|string']);
        [$ok, $tahun, $periodeKe, $start, $end] = $this->parsePeriodeString($request->input('periode'));
        if (!$ok) {
            return response()->json(['success' => false, 'message' => 'Format periode tidak valid. Gunakan TAHUN-PERIODE, mis: 2025-1'], 422);
        }

        $periodeStr = "{$tahun}-{$periodeKe}";

        $rows = DB::table('tbhs_absensi as a')
            ->select(
                'a.id_admin','a.nama_staff','a.id_situs',
                DB::raw("'{$periodeStr}' as periode"),
                DB::raw("SUM(CASE WHEN a.status LIKE '%SAKIT%' THEN 1 ELSE 0 END)        as sakit"),
                DB::raw("SUM(CASE WHEN a.status LIKE '%IZIN%' THEN 1 ELSE 0 END)         as izin"),
                DB::raw("SUM(CASE WHEN a.status LIKE '%TELAT%' THEN 1 ELSE 0 END)        as telat"),
                DB::raw("SUM(CASE WHEN a.status LIKE '%TANPA KABAR%' THEN 1 ELSE 0 END)  as tanpa_kabar"),
                DB::raw("SUM(CASE WHEN a.status LIKE '%CUTI%' THEN 1 ELSE 0 END)         as cuti"),
                DB::raw("COUNT(*) as total_absensi")
            )
            ->whereBetween('a.tanggal', [$start, $end])
            ->where('a.soft_delete', 0)
            ->groupBy('a.id_admin','a.nama_staff','a.id_situs')
            ->get();

        DB::transaction(function () use ($rows, $periodeStr) {
            // 1) Hapus semua ringkasan periode ini → mencegah data stale tertinggal
            \App\Models\AbsensiReport::where('periode', $periodeStr)->delete();

            if ($rows->isEmpty()) {
                return; // tidak ada yang di-insert; tabel kosong untuk periode ini, sesuai data sumber
            }

            // 2) Insert ulang payload terbaru
            $payload = $rows->map(function ($r) {
                return [
                    'id_admin'      => $r->id_admin,
                    'id_situs'      => $r->id_situs,
                    'periode'       => $r->periode,
                    'nama_staff'    => $r->nama_staff,
                    'sakit'         => (int) $r->sakit,
                    'izin'          => (int) $r->izin,
                    'telat'         => (int) $r->telat,
                    'tanpa_kabar'   => (int) $r->tanpa_kabar,
                    'cuti'          => (int) $r->cuti,
                    'total_absensi' => (int) $r->total_absensi,
                ];
            })->all();

            \App\Models\AbsensiReport::insert($payload);
        });

        return response()->json([
            'success' => true,
            'message' => 'Report periode berhasil di generate.',
            'total'   => $rows->count(),
        ]);
    }

    // GET /hrdmanagement/report-absensi/export
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
        $fileName      = 'report_absensi_' . $tahun . $suffixPeriode . $suffixSitus . '.xlsx';

        return Excel::download(
            new AbsensiReportExport($tahun, $periode, $idSitusList ?: null),
            $fileName
        );
    }
}
