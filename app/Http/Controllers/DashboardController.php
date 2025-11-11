<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Pilih periode aktif dengan minimal jumlah topik unik.
     * - Null-safe saat koleksi kosong.
     */
    private function pickPeriodeAktif(?string $forcedPeriode = null, int $minUniqTopics = 10): ?string
    {
        if ($forcedPeriode) return $forcedPeriode;

        // Normalisasi aman UTF-8: hilangkan NBSP/TAB/LF/CR lalu TRIM, NULL -> ''
        $NBSP = "CONVERT(0xC2A0 USING utf8mb4)";
        $norm = "
            UPPER(
              TRIM(
                REPLACE(
                  REPLACE(
                    REPLACE(
                      REPLACE(IFNULL(f.topik_title,''), $NBSP, ' '), CHAR(9), ' '
                    ), CHAR(10), ' '
                  ), CHAR(13), ' '
                )
              )
            )
        ";

        $rows = DB::table('tbhs_forum as f')
            ->selectRaw("f.periode, COUNT(DISTINCT $norm) AS uniq_topics")
            ->where('f.soft_delete', '0')
            ->groupBy('f.periode')
            ->orderByDesc('f.periode')
            ->get();

        $ok = $rows->firstWhere('uniq_topics', '>=', $minUniqTopics);

        // Null-safe ambil periode pertama jika tidak ada yang memenuhi minUniqTopics
        return data_get($ok, 'periode', data_get($rows->first(), 'periode'));
    }

    /** Map parameter status -> scope status_case. */
    private function resolveStatusScope(?string $statusParam): array
    {
        $statusParam = strtolower(trim($statusParam ?? 'active'));
        switch ($statusParam) {
            case 'open':      return [[1], 'Open'];
            case 'pending':   return [[2], 'Pending'];
            case 'progress':
            case 'onprogress':return [[3], 'On Progress'];
            case 'closed':
            case 'close':     return [[4], 'Closed'];
            case 'all':       return [[1,2,3,4], 'Semua Status'];
            case 'active':
            default:          return [[1,2,3], 'Open + Pending + On Progress'];
        }
    }

    /** ===== Util palet warna & kontras teks ===== */
    private function colorAt(float $r): array
    {
        /**
         * PALET 6 warna (high->low):
         * merah -> oranye -> kuning -> yellow-green -> hijau muda -> putih
         * pos 1 = terbanyak, pos 0 = tersedikit
         */
        $palette = [
            [1.00, [255,   0,   0]], // merah
            [0.78, [255, 128,   0]], // oranye
            [0.56, [255, 210,   0]], // kuning
            [0.36, [173, 255,  47]], // yellow-green
            [0.18, [144, 238, 144]], // hijau muda
            [0.00, [255, 255, 255]], // putih
        ];

        $r = max(0, min(1, $r));
        for ($i = 0; $i < count($palette) - 1; $i++) {
            [$pHi, $cHi] = $palette[$i];
            [$pLo, $cLo] = $palette[$i + 1];
            if ($r <= $pHi && $r >= $pLo) {
                $t = ($pHi - $pLo) > 0 ? ($r - $pLo) / ($pHi - $pLo) : 0;
                $R = (int) round($cLo[0] + ($cHi[0] - $cLo[0]) * $t);
                $G = (int) round($cLo[1] + ($cHi[1] - $cLo[1]) * $t);
                $B = (int) round($cLo[2] + ($cHi[2] - $cLo[2]) * $t);
                return [$R, $G, $B];
            }
        }
        return [255, 255, 255];
    }

    private function textFor(array $rgb): string
    {
        $luma = 0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2];
        return $luma < 150 ? '#ffffff' : '#111111';
    }

    /** Dashboard — Topik Ranking (periode & scope status). */
    public function index(Request $request)
    {
        $search        = $request->query('q');
        $currentTab    = $request->query('tab', 'topik_title'); // dipakai Blade
        $periodeParam  = $request->query('periode');
        [$statusScope, $statusLabel] = $this->resolveStatusScope($request->query('status'));

        $periodeAktif = $this->pickPeriodeAktif($periodeParam, 10);

        // 🔒 PRIVILEGE: pindah dari Blade ke controller (aman pakai data_get)
        $authLink = \App\Helpers\AuthLink::access_url(Auth::user()->id_admin, 'dashinfo');
        $canSeeTopicLinks = (int) data_get($authLink, '0.nilai', 0) > 0;

        // Label & sentinel utk judul kosong
        $NO_KEY   = '__NO_TOPIC__';
        $NO_LABEL = 'LAINNYA / TANPA TOPIK';

        // Normalisasi UTF-8 (NULL -> '') + buang NBSP/TAB/LF/CR + TRIM
        $NBSP     = "CONVERT(0xC2A0 USING utf8mb4)";
        $rawTitle = "
            TRIM(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(IFNULL(f.topik_title,''), $NBSP, ' ')
                        , CHAR(9),  ' ')
                    , CHAR(10), ' ')
                , CHAR(13), ' ')
            )
        ";
        $normKey  = "CASE WHEN ($rawTitle='' OR $rawTitle='0') THEN '$NO_KEY' ELSE UPPER($rawTitle) END";

        // === Agregasi jumlah per key_title ===
        $forumAgg = DB::table('tbhs_forum as f')
            ->selectRaw("$normKey AS key_title, COUNT(*) AS total")
            ->where('f.soft_delete', '0')
            ->where('f.periode', $periodeAktif)
            ->whereIn('f.status_case', $statusScope)
            ->groupBy('key_title');

        // === Sumber judul valid (bukan kosong/0) ===
        $titlesValid = DB::table('tbhs_forum as f')
            ->selectRaw("
                $normKey AS key_title,
                SUBSTRING_INDEX(
                    GROUP_CONCAT(
                      DISTINCT $rawTitle
                      ORDER BY CHAR_LENGTH($rawTitle) DESC SEPARATOR '||'
                    ), '||', 1
                ) AS topik_title
            ")
            ->where('f.soft_delete', '0')
            ->where('f.periode', $periodeAktif)
            ->whereIn('f.status_case', $statusScope)
            ->whereRaw("($rawTitle <> '' AND $rawTitle <> '0')")
            ->when($search, fn ($q) => $q->whereRaw("$rawTitle LIKE ?", ["%{$search}%"]))
            ->groupBy('key_title');

        // === Satu baris untuk judul kosong/0 (hanya muncul jika memang ada) ===
        $titlesBlank = DB::table('tbhs_forum as f')
            ->selectRaw("'$NO_KEY' AS key_title, '$NO_LABEL' AS topik_title")
            ->where('f.soft_delete', '0')
            ->where('f.periode', $periodeAktif)
            ->whereIn('f.status_case', $statusScope)
            ->whereRaw("($rawTitle='' OR $rawTitle='0')")
            ->limit(1);

        // UNION keduanya, lalu pastikan tiap key hanya satu label
        $titlesAll = $titlesValid->union($titlesBlank);

        $normalizedTitles = DB::query()
            ->fromSub($titlesAll, 't')
            ->selectRaw('t.key_title, MIN(t.topik_title) AS topik_title')
            ->groupBy('t.key_title');

        // Join ke agregat
        $topik = DB::query()
            ->fromSub($normalizedTitles, 't')
            ->joinSub($forumAgg, 'f', 't.key_title', '=', 'f.key_title')
            ->selectRaw('t.topik_title, f.total AS cases_count')
            ->orderByDesc('cases_count')
            ->orderBy('t.topik_title')
            ->get();

        $maxCases = max(1, (int) $topik->max('cases_count'));

        /** ====== Siapkan data kartu untuk Blade (warna, teks, URL) ====== */
        $topikCards = $topik->map(function ($row) use ($maxCases, $periodeAktif, $request) {
            $n        = (int) ($row->cases_count ?? 0);
            $ratio    = $maxCases ? ($n / $maxCases) : 0;      // 0..1
            $ratioAdj = pow($ratio, 0.88);                     // boost kecil

            // ⬇️ pakai list() biar aman di semua lingkungan
            list($r, $g, $b) = $this->colorAt($ratioAdj);

            $bg        = "rgb($r, $g, $b)";
            // ⬇️ pakai array() (hindari [] di argumen fungsi)
            $textColor = $this->textFor(array($r, $g, $b));
            $border    = $n === 0 ? '#eaeaea' : 'transparent';

            return [
                'count'     => $n,
                'title'     => $row->topik_title,
                'bg'        => $bg,
                'textColor' => $textColor,
                'border'    => $border,
                'moreUrl'   => route('dashboard.topicPage', [
                    'topic'   => $row->topik_title,
                    'periode' => $periodeAktif,
                    'status'  => $request->input('status', 'active'),
                    'src'     => 'admin',
                ]),
            ];
        })->values();

        // URL leaderboard ikut bawa periode aktif supaya konsisten
        $leaderboardUrl = isset($periodeAktif)
            ? route('dashboard.leaderboard', ['periode' => $periodeAktif])
            : route('dashboard.leaderboard');

        return view('pages.dashboard', compact(
            'topik',
            'topikCards',
            'maxCases',
            'currentTab',
            'search',
            'periodeAktif',
            'statusLabel',
            'leaderboardUrl',
            'canSeeTopicLinks'
        ));
    }

    /** KPI (status_case=4) tetap per-periode. */
    public function leaderboard(Request $request)
    {
        $periodeParam    = $request->query('periode');
        $periodeSekarang = $this->pickPeriodeAktif($periodeParam, 1);

        $activeUsers = DB::table('tbhs_users as u')
            ->join('tbhs_jabatan as j', 'u.id_jabatan', '=', 'j.id')
            ->where('u.status', 1)
            ->whereNotIn('j.id', [1, 2, 9, 13, 14])
            ->select('u.nama_staff')
            ->get();

        $staffNames = $activeUsers->pluck('nama_staff')->toArray();

        $forumAggregated = DB::table('tbhs_forum as f')
            ->select(
                'f.created_for_name',
                DB::raw('COUNT(DISTINCT f.id) as total_cases'),
                DB::raw('SUM(CASE WHEN f.status_kesalahan = 1 THEN 1 ELSE 0 END) as total_no_fault'),
                DB::raw('SUM(CASE WHEN f.status_kesalahan IN (2, 3, 4) THEN 1 ELSE 0 END) as total_fault')
            )
            ->where('f.soft_delete', '0')
            ->where('f.status_case', 4)
            ->where('f.periode', $periodeSekarang)
            ->groupBy('f.created_for_name');

        $bad = DB::table('tbhs_users as u')
            ->leftJoinSub($forumAggregated, 'f', 'u.nama_staff', '=', 'f.created_for_name')
            ->whereIn('u.nama_staff', $staffNames)
            ->select(
                'u.nama_staff',
                DB::raw('IFNULL(f.total_cases, 0) as total_cases'),
                DB::raw('IFNULL(f.total_no_fault, 0) as total_no_fault'),
                DB::raw('IFNULL(f.total_fault, 0) as total_fault')
            )
            ->distinct()
            ->orderByDesc('total_fault')
            ->orderByDesc('total_cases')
            ->orderBy('u.nama_staff')
            ->limit(20)
            ->get();

        $good = DB::table('tbhs_users as u')
            ->leftJoinSub($forumAggregated, 'f', 'u.nama_staff', '=', 'f.created_for_name')
            ->whereIn('u.nama_staff', $staffNames)
            ->select(
                'u.nama_staff',
                DB::raw('IFNULL(f.total_cases, 0) as total_cases'),
                DB::raw('IFNULL(f.total_no_fault, 0) as total_no_fault'),
                DB::raw('IFNULL(f.total_fault, 0) as total_fault')
            )
            ->distinct()
            ->orderByDesc('total_no_fault')
            ->orderBy('total_fault', 'asc')
            ->orderByDesc('total_cases')
            ->orderBy('u.nama_staff')
            ->limit(20)
            ->get();

        return response()->json(['bad' => $bad, 'good' => $good]);
    }

    /** Halaman list kasus per topik. */
    public function topicPage(Request $request)
    {
        $topic         = trim($request->query('topic', ''));
        $periodeParam  = $request->query('periode');
        [$statusScope, $statusLabel] = $this->resolveStatusScope($request->query('status'));

        if ($topic === '') {
            return redirect()->route('dashboard.index');
        }

        $periodeAktif = $this->pickPeriodeAktif($periodeParam, 1);

        $src = $request->query('src', 'any'); // 'admin' | 'auditor' | 'any'
        $colIdLabel = $src === 'admin' ? 'ID Admin' : ($src === 'auditor' ? 'ID Auditor' : 'ID Auditor/Admin');

        // Template URL detail auditor (pakai route kalau ada, fallback ke URL manual)
        if (Route::has('auditorforum.auditorpostdetails')) {
            $auditorDetailUrlTemplate = route('auditorforum.auditorpostdetails', ['slug' => '__SLUG__']);
        } else {
            $auditorDetailUrlTemplate = url('/auditorforum/auditorpostdetails/__SLUG__');
        }

        // Konfigurasi AJAX DataTables dipindah ke controller
        $dtAjaxUrl    = route('dashboard.topicCases');
        $dtAjaxParams = [
            'topic'   => $topic,
            'periode' => $periodeAktif,
            'status'  => 'all',      // tampilkan semua status
            'src'     => 'auditor',
        ];

        return view('pages.topic-by-title', compact(
            'topic',
            'periodeAktif',
            'statusLabel',
            'src',
            'colIdLabel',
            'auditorDetailUrlTemplate',
            'dtAjaxUrl',
            'dtAjaxParams'
        ));
    }

    /** JSON untuk DataTables pada halaman topicPage. */
    public function topicCases(Request $request)
    {
        $topic         = trim($request->query('topic', ''));
        $periodeParam  = $request->query('periode');
        [$statusScope] = $this->resolveStatusScope($request->query('status'));
        $periodeAktif  = $this->pickPeriodeAktif($periodeParam, 1);
        $src           = $request->query('src', 'any'); // 'admin' | 'auditor' | 'any'

        // ====== DETEKSI KOLOM ======
        $cols = DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'tbhs_forum')
            ->pluck('COLUMN_NAME')
            ->toArray();

        $hasIdAuditor = in_array('id_auditor', $cols, true);
        $hasIdAdmin   = in_array('id_admin', $cols, true);
        $hasCreatedBy = in_array('created_by', $cols, true);
        $hasSlug      = in_array('slug', $cols, true);

        // Pilih kolom "petugas" sesuai sumber yg diminta.
        if ($src === 'admin' && $hasIdAdmin) {
            $selectPetugas = 'f.id_admin';
        } elseif ($src === 'auditor' && $hasIdAuditor) {
            $selectPetugas = 'f.id_auditor';
        } else {
            // fallback: pakai yang ada (COALESCE)
            $pieces = [];
            if ($hasIdAuditor) $pieces[] = 'f.id_auditor';
            if ($hasIdAdmin)   $pieces[] = 'f.id_admin';
            if ($hasCreatedBy) $pieces[] = 'f.created_by';
            $selectPetugas = count($pieces) ? 'COALESCE(' . implode(',', $pieces) . ')' : 'NULL';
        }

        // pilih slug kalau ada, fallback ke id
        $selectSlug = $hasSlug ? 'f.slug' : 'f.id';

        // ====== NORMALISASI JUDUL (konsisten dgn index()) ======
        $NO_LABEL = 'LAINNYA / TANPA TOPIK';
        $NBSP     = "CONVERT(0xC2A0 USING utf8mb4)";
        $rawTitle = "
            TRIM(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(IFNULL(f.topik_title,''), $NBSP, ' ')
                        , CHAR(9),  ' ')
                    , CHAR(10), ' ')
                , CHAR(13), ' ')
            )
        ";
        $normCol   = "UPPER($rawTitle)";
        $normParam = "
            UPPER(
              TRIM(
                REPLACE(
                  REPLACE(
                    REPLACE(
                      REPLACE(IFNULL(?,''), $NBSP, ' ')
                    , CHAR(9),  ' ')
                  , CHAR(10), ' ')
                , CHAR(13), ' ')
              )
            )
        ";

        // ====== QUERY DATA ======
        $rows = DB::table('tbhs_forum as f')
            ->selectRaw("
                $selectSlug                                   AS slug,
                f.id                                          AS topik_id,
                TRIM(f.topik_title)                           AS topik_title,
                f.created_for_name                            AS created_for_name,
                DATE_FORMAT(f.created_at,'%Y-%m-%d %H:%i:%s') AS created_at,
                $selectPetugas                                AS id_auditor, -- alias tetap 'id_auditor' agar kompatibel dgn view
                f.status_case                                 AS status_code,
                CASE f.status_case
                  WHEN 1 THEN 'Open'
                  WHEN 2 THEN 'Pending'
                  WHEN 3 THEN 'On Progress'
                  WHEN 4 THEN 'Close'
                  ELSE '-'
                END                                           AS status_text
            ")
            ->where('f.soft_delete', '0')
            ->where('f.periode', $periodeAktif)
            ->whereIn('f.status_case', $statusScope)
            // filter sumber data (hanya jika kolomnya memang ada)
            ->when($src === 'admin'   && $hasIdAdmin,   fn($q) => $q->whereRaw("COALESCE(f.id_admin,'') <> ''"))
            ->when($src === 'auditor' && $hasIdAuditor, fn($q) => $q->whereRaw("COALESCE(f.id_auditor,'') <> ''"))
            // filter topik
            ->when($topic !== '', function ($q) use ($topic, $NO_LABEL, $rawTitle, $normCol, $normParam) {
                if (strcasecmp($topic, $NO_LABEL) === 0) {
                    $q->whereRaw("($rawTitle='' OR $rawTitle='0')");
                } else {
                    $q->whereRaw("$normCol = $normParam", [$topic]);
                }
            })
            ->orderByDesc('f.created_at')
            ->get();

        return response()->json(['data' => $rows]);
    }

    /** JSON: semua kasus pada periode aktif (default: status 1–4). */
    public function allCases(Request $request)
    {
        $periodeParam = $request->query('periode');
        [$statusScope] = $this->resolveStatusScope($request->query('status', 'all'));
        $periodeAktif  = $this->pickPeriodeAktif($periodeParam, 1);

        $hasIdAdmin = DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'tbhs_forum')
            ->where('COLUMN_NAME', 'id_admin')->exists();

        $hasSlug = DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'tbhs_forum')
            ->where('COLUMN_NAME', 'slug')->exists();

        $selectIdAdmin = $hasIdAdmin ? 'f.id_admin' : 'NULL';
        $selectSlug    = $hasSlug ? 'f.slug' : 'f.id';

        $rows = DB::table('tbhs_forum as f')
            ->selectRaw("
                $selectSlug                                   AS slug,
                f.id                                          AS topik_id,
                TRIM(f.topik_title)                           AS topik_title,
                f.created_for_name                            AS created_for_name,
                DATE_FORMAT(f.created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
                $selectIdAdmin                                AS id_admin,
                CASE f.status_case
                  WHEN 1 THEN 'Open'
                  WHEN 2 THEN 'Pending'
                  WHEN 3 THEN 'On Progress'
                  WHEN 4 THEN 'Close'
                  ELSE '-'
                END                                           AS status_text
            ")
            ->where('f.soft_delete', '0')
            ->where('f.periode', $periodeAktif)
            ->whereIn('f.status_case', $statusScope)
            ->orderByDesc('f.created_at')
            ->get();

        return response()->json(['data' => $rows]);
    }
}
