<?php

namespace App\Http\Controllers;

use App\Models\Forumaudit;   // tbhs_forum
use App\Models\User;         // tbhs_users
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /** sentinel untuk judul kosong */
    public const NO_KEY   = '__NO_TOPIC__';
    public const NO_LABEL = 'LAINNYA / TANPA TOPIK';

    public function __construct()
    {
        $this->middleware('auth');
    }

    /* =========================
     * Helpers (semua public)
     * ======================= */

    /** Ekspresi SQL untuk NBSP (UTF-8) – dipakai di normalisasi judul. */
    public function nbspExpr(): string
    {
        return "CONVERT(0xC2A0 USING utf8mb4)";
    }

    /**
     * Ekspresi SQL untuk normalisasi judul:
     * - NULL => '' ; hilangkan NBSP/TAB/LF/CR ; TRIM; UPPER (opsional).
     * @param bool   $upper apakah di-UPPER
     * @param string $col   kolom sumber, default f.topik_title
     */
    public function normalizedTitleExpr(bool $upper = true, string $col = 'f.topik_title'): string
    {
        $NBSP = $this->nbspExpr();
        $expr = "
            TRIM(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(IFNULL($col,''), $NBSP, ' ')
                        , CHAR(9),  ' ')
                    , CHAR(10), ' ')
                , CHAR(13), ' ')
            )
        ";
        return $upper ? "UPPER($expr)" : $expr;
    }

    /** Label status_case dalam SQL */
    public function statusLabelSql(string $col = 'f.status_case'): string
    {
        return "
            CASE $col
              WHEN 1 THEN 'Open'
              WHEN 2 THEN 'Pending'
              WHEN 3 THEN 'On Progress'
              WHEN 4 THEN 'Close'
              ELSE '-'
            END
        ";
    }

    /** Map query param status -> [daftar kode, label ringkas] */
    public function resolveStatusScope(?string $param): array
    {
        $p = strtolower(trim($param ?? 'active'));
        return match ($p) {
            'open'                    => [[1], 'Open'],
            'pending'                 => [[2], 'Pending'],
            'progress', 'onprogress'  => [[3], 'On Progress'],
            'closed', 'close'         => [[4], 'Closed'],
            'all'                     => [[1,2,3,4], 'Semua Status'],
            default                   => [[1,2,3], 'Open + Pending + On Progress'], // active
        };
    }

    /**
     * Ambil periode aktif; jika tidak dipaksa, pilih periode dgn minimal topik unik.
     * @return string|null
     */
    public function pickPeriodeAktif(?string $forced = null, int $minUniqTopics = 10): ?string
    {
        if ($forced) return $forced;

        $norm = $this->normalizedTitleExpr(true, 'f.topik_title');

        $rows = Forumaudit::query()
            ->from('tbhs_forum as f')
            ->selectRaw("f.periode, COUNT(DISTINCT $norm) AS uniq_topics")
            ->where('f.soft_delete', '0')
            ->groupBy('f.periode')
            ->orderByDesc('f.periode')
            ->get();

        $ok = $rows->firstWhere('uniq_topics', '>=', $minUniqTopics);
        return data_get($ok, 'periode', data_get($rows->first(), 'periode'));
    }

    /** Daftar kolom yang tersedia pada tbhs_forum (via Schema) */
    public function forumColumns(): array
    {
        $cols = [];
        foreach (['id_auditor','id_admin','created_by','slug'] as $c) {
            if (Schema::hasColumn('tbhs_forum', $c)) $cols[] = $c;
        }
        return $cols;
    }

    /** Pilih kolom petugas sesuai sumber (admin|auditor|any). */
    public function selectPetugas(string $src, array $cols): string
    {
        $hasIdAuditor = in_array('id_auditor', $cols, true);
        $hasIdAdmin   = in_array('id_admin',   $cols, true);
        $hasCreatedBy = in_array('created_by', $cols, true);

        if ($src === 'admin'   && $hasIdAdmin)   return 'f.id_admin';
        if ($src === 'auditor' && $hasIdAuditor) return 'f.id_auditor';

        $pieces = [];
        if ($hasIdAuditor) $pieces[] = 'f.id_auditor';
        if ($hasIdAdmin)   $pieces[] = 'f.id_admin';
        if ($hasCreatedBy) $pieces[] = 'f.created_by';

        return $pieces ? 'COALESCE(' . implode(',', $pieces) . ')' : 'NULL';
    }

    /** Pilih kolom slug (fallback ke id). */
    public function selectSlug(array $cols): string
    {
        return in_array('slug', $cols, true) ? 'f.slug' : 'f.id';
    }

    /** Format created_at lokal (UTC -> UTC+7). */
    public function sqlLocalCreatedAt(string $col = 'f.created_at'): string
    {
        // Jika DB sudah lokal, ganti dengan: "DATE_FORMAT($col, '%Y-%m-%d %H:%i:%s')"
        return "DATE_FORMAT(CONVERT_TZ($col, '+00:00', '+07:00'), '%Y-%m-%d %H:%i:%s')";
    }

    /* =========================
     * Controller actions
     * ======================= */

    /** Dashboard — Topik Ranking. */
    public function index(Request $request)
    {
        $search        = $request->query('q');
        $currentTab    = $request->query('tab', 'topik_title');
        $periodeParam  = $request->query('periode');
        [$statusScope, $statusLabel] = $this->resolveStatusScope($request->query('status'));
        $periodeAktif  = $this->pickPeriodeAktif($periodeParam, 10);

        // privilege tombol "Info Lebih Lanjut"
        $authLink = \App\Helpers\AuthLink::access_url(Auth::user()->id_admin, 'dashinfo');
        $canSeeTopicLinks = (int) data_get($authLink, '0.nilai', 0) > 0;

        // ekspresi normalisasi judul
        $rawTitle = $this->normalizedTitleExpr(false, 'f.topik_title');
        $normKey  = "CASE WHEN ($rawTitle='' OR $rawTitle='0') THEN '".self::NO_KEY."' ELSE UPPER($rawTitle) END";

        // agregasi jumlah per judul-normal (key_title)
        $forumAgg = Forumaudit::query()
            ->from('tbhs_forum as f')
            ->selectRaw("$normKey AS key_title, COUNT(*) AS total")
            ->where('f.soft_delete', '0')
            ->where('f.periode', $periodeAktif)
            ->whereIn('f.status_case', $statusScope)
            ->groupBy('key_title');

        // kandidat judul valid
        $titlesValid = Forumaudit::query()
            ->from('tbhs_forum as f')
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

        // satu baris utk judul kosong
        $titlesBlank = Forumaudit::query()
            ->from('tbhs_forum as f')
            ->selectRaw("'".self::NO_KEY."' AS key_title, '".self::NO_LABEL."' AS topik_title")
            ->where('f.soft_delete', '0')
            ->where('f.periode', $periodeAktif)
            ->whereIn('f.status_case', $statusScope)
            ->whereRaw("($rawTitle='' OR $rawTitle='0')")
            ->limit(1);

        // pastikan 1 label per key
        $normalizedTitles = Forumaudit::query()
            ->fromSub($titlesValid->union($titlesBlank), 't')
            ->selectRaw('t.key_title, MIN(t.topik_title) AS topik_title')
            ->groupBy('t.key_title');

        // join ke agregat → daftar topik
        $topik = Forumaudit::query()
            ->fromSub($normalizedTitles, 't')
            ->joinSub($forumAgg, 'f', 't.key_title', '=', 'f.key_title')
            ->selectRaw('t.topik_title, f.total AS cases_count')
            ->orderByDesc('cases_count')
            ->orderBy('t.topik_title')
            ->get();

        $maxCases = max(1, (int) $topik->max('cases_count'));

        // bahan untuk kartu (ratio di-boost)
        $topikCards = $topik->map(function ($row) use ($maxCases, $periodeAktif, $request) {
            $n     = (int) ($row->cases_count ?? 0);
            $ratio = $maxCases ? pow(($n / $maxCases), 0.88) : 0;

            return [
                'count'   => $n,
                'title'   => $row->topik_title,
                'ratio'   => $ratio,
                'moreUrl' => route('dashboard.topicPage', [
                    'topic'   => $row->topik_title,
                    'periode' => $periodeAktif,
                    'status'  => $request->input('status', 'active'),
                    'src'     => 'admin',
                ]),
            ];
        })->values();

        $leaderboardUrl = $periodeAktif
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

    /** KPI (status_case=4) per-periode. */
    public function leaderboard(Request $request)
    {
        $periode = $this->pickPeriodeAktif($request->query('periode'), 1);

        // nama staff aktif (join jabatan) → via model User
        $activeNames = User::query()
            ->from('tbhs_users as u')
            ->join('tbhs_jabatan as j', 'u.id_jabatan', '=', 'j.id')
            ->where('u.status', 1)
            ->whereNotIn('j.id', [1, 2, 9, 13, 14])
            ->pluck('u.nama_staff');

        // subquery agregasi forum (via Forumaudit)
        $forumAgg = Forumaudit::query()
            ->from('tbhs_forum as f')
            ->selectRaw("
                f.created_for_name,
                COUNT(DISTINCT f.id) as total_cases,
                SUM(CASE WHEN f.status_kesalahan = 1 THEN 1 ELSE 0 END) as total_no_fault,
                SUM(CASE WHEN f.status_kesalahan IN (2, 3, 4) THEN 1 ELSE 0 END) as total_fault
            ")
            ->where('f.soft_delete', '0')
            ->where('f.status_case', 4)
            ->where('f.periode', $periode)
            ->groupBy('f.created_for_name');

        // worst
        $bad = User::query()
            ->from('tbhs_users as u')
            ->leftJoinSub($forumAgg, 'f', 'u.nama_staff', '=', 'f.created_for_name')
            ->whereIn('u.nama_staff', $activeNames)
            ->selectRaw("
                u.nama_staff,
                IFNULL(f.total_cases, 0)   as total_cases,
                IFNULL(f.total_no_fault, 0) as total_no_fault,
                IFNULL(f.total_fault, 0)   as total_fault
            ")
            ->distinct()
            ->orderByDesc('total_fault')
            ->orderByDesc('total_cases')
            ->orderBy('u.nama_staff')
            ->limit(20)
            ->get();

        // best
        $good = User::query()
            ->from('tbhs_users as u')
            ->leftJoinSub($forumAgg, 'f', 'u.nama_staff', '=', 'f.created_for_name')
            ->whereIn('u.nama_staff', $activeNames)
            ->selectRaw("
                u.nama_staff,
                IFNULL(f.total_cases, 0)   as total_cases,
                IFNULL(f.total_no_fault, 0) as total_no_fault,
                IFNULL(f.total_fault, 0)   as total_fault
            ")
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
        $topic = trim($request->query('topic', ''));
        if ($topic === '') return redirect()->route('dashboard.index');

        $periodeAktif    = $this->pickPeriodeAktif($request->query('periode'), 1);
        [, $statusLabel] = $this->resolveStatusScope($request->query('status'));
        $src = $request->query('src', 'any'); // 'admin' | 'auditor' | 'any'
        $colIdLabel = $src === 'admin' ? 'ID Admin' : ($src === 'auditor' ? 'ID Auditor' : 'ID Auditor/Admin');

        $auditorDetailUrlTemplate = Route::has('auditorforum.auditorpostdetails')
            ? route('auditorforum.auditorpostdetails', ['slug' => '__SLUG__'])
            : url('/auditorforum/auditorpostdetails/__SLUG__');

        $dtAjaxUrl    = route('dashboard.topicCases');
        $dtAjaxParams = [
            'topic'   => $topic,
            'periode' => $periodeAktif,
            'status'  => 'all',
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

    /** JSON untuk DataTables di topicPage. */
    public function topicCases(Request $request)
    {
        $topic        = trim($request->query('topic', ''));
        $periodeAktif = $this->pickPeriodeAktif($request->query('periode'), 1);
        [$statusScope] = $this->resolveStatusScope($request->query('status'));
        $src          = $request->query('src', 'any');

        $cols          = $this->forumColumns();
        $selectPetugas = $this->selectPetugas($src, $cols);
        $selectSlug    = $this->selectSlug($cols);

        $rawTitle  = $this->normalizedTitleExpr(false, 'f.topik_title');
        $normCol   = "UPPER($rawTitle)";
        $normParam = "UPPER(".$this->normalizedTitleExpr(false, ' ? ').")";

        $createdAtSql = $this->sqlLocalCreatedAt('f.created_at');

        $rows = Forumaudit::query()
            ->from('tbhs_forum as f')
            ->selectRaw("
                $selectSlug                                     AS slug,
                f.id                                            AS topik_id,
                TRIM(f.topik_title)                             AS topik_title,
                f.created_for_name                              AS created_for_name,
                $createdAtSql                                   AS created_at,
                $selectPetugas                                  AS id_auditor,
                f.status_case                                   AS status_code,
                {$this->statusLabelSql()}                       AS status_text
            ")
            ->where('f.soft_delete', '0')
            ->where('f.periode', $periodeAktif)
            ->whereIn('f.status_case', $statusScope)
            ->when($src === 'admin'   && in_array('id_admin',   $cols, true), fn($q) => $q->whereRaw("COALESCE(f.id_admin,'') <> ''"))
            ->when($src === 'auditor' && in_array('id_auditor', $cols, true), fn($q) => $q->whereRaw("COALESCE(f.id_auditor,'') <> ''"))
            ->when($topic !== '', function ($q) use ($topic, $rawTitle, $normCol, $normParam) {
                if (strcasecmp($topic, self::NO_LABEL) === 0) {
                    $q->whereRaw("($rawTitle='' OR $rawTitle='0')");
                } else {
                    $q->whereRaw("$normCol = $normParam", [$topic]);
                }
            })
            ->orderByDesc('f.created_at')
            ->toBase() // penting: hindari casting Eloquent ISO8601
            ->get();

        return response()->json(['data' => $rows]);
    }

    /** JSON: semua kasus pada periode aktif. */
    public function allCases(Request $request)
    {
        $periodeAktif  = $this->pickPeriodeAktif($request->query('periode'), 1);
        [$statusScope] = $this->resolveStatusScope($request->query('status', 'all'));

        $cols        = $this->forumColumns();
        $selectSlug  = $this->selectSlug($cols);
        $selectAdmin = in_array('id_admin', $cols, true) ? 'f.id_admin' : 'NULL';

        $createdAtSql = $this->sqlLocalCreatedAt('f.created_at');

        $rows = Forumaudit::query()
            ->from('tbhs_forum as f')
            ->selectRaw("
                $selectSlug                                     AS slug,
                f.id                                            AS topik_id,
                TRIM(f.topik_title)                             AS topik_title,
                f.created_for_name                              AS created_for_name,
                $createdAtSql                                   AS created_at,
                $selectAdmin                                    AS id_admin,
                {$this->statusLabelSql()}                       AS status_text
            ")
            ->where('f.soft_delete', '0')
            ->where('f.periode', $periodeAktif)
            ->whereIn('f.status_case', $statusScope)
            ->orderByDesc('f.created_at')
            ->toBase()
            ->get();

        return response()->json(['data' => $rows]);
    }
}
