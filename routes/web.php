<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TopikController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuditFormController;   // ← perbaikan kapitalisasi
use App\Http\Controllers\SitusController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OlddataController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\MigratedbController;
use App\Http\Controllers\OtherController;
use App\Http\Controllers\HsForumController;
use App\Http\Controllers\HrdManagementController;
use App\Http\Controllers\AbsensiController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/auth/login')->name('login');

/* =========================
   Auth
   ========================= */
Route::controller(AuthController::class)->group(function () {
    Route::get('/auth/login', 'loginPage')->name('login.page');
    Route::post('/process', 'login')->name('login.do');
    Route::get('/logout', 'logout')->name('logout');
});

/* =========================
   Dashboard
   ========================= */
Route::middleware('auth')->prefix('dashboard')->as('dashboard.')->group(function () {
    Route::get('/',            [DashboardController::class, 'index'])->name('index');
    Route::get('/leaderboard', [DashboardController::class, 'leaderboard'])->name('leaderboard');

    // 📄 Halaman full (list kasus berdasarkan topik)
    Route::get('/topic',       [DashboardController::class, 'topicPage'])->name('topicPage');

    // 🔎 DataTables JSON (Ajax)
    Route::get('/topic-cases', [DashboardController::class, 'topicCases'])->name('topicCases');

    // 🧾 Semua kasus periode aktif (JSON)
    Route::get('/all-cases',   [DashboardController::class, 'allCases'])->name('allCases');
});

/* =========================
   HRD MANAGEMENT
   ========================= */
Route::prefix('hrdmanagement')->as('hrdmanagement.')->controller(HrdManagementController::class)->group(function () {
    Route::get('/', 'index')->name('index'); 
    Route::put('absensi/{id}', 'updateAbsensi')->name('absensi.update'); 
    Route::post('absensi/save', 'storeAbsensi')->name('absensi.save'); 
    Route::get('absensi/list', 'listAbsensi')->name('absensi.list');
    Route::delete('absensi/{id}', 'destroyAbsensi')->name('absensi.destroy');
    Route::get('absensi/check-duplicate', 'checkAbsensiDuplicate')->name('absensi.check_duplicate');
    Route::get('staff/data', 'staffData')->name('staff.data');
    Route::get('report-absensi', 'reportingAbsensi')->name('reportingabsensi');
    Route::get('report-absensi/data', 'getReportingAbsensiData')->name('reportingabsensi.data');
    Route::post('report-absensi/generate', 'generateAbsensiReport')->name('reportingabsensi.generate');
    Route::get('report-absensi/export', 'exportAbsensiReport')->name('reportingabsensi.export');
    Route::get('grafik', 'grafik')->name('grafik');
    Route::get('absensi/detail', 'detailAbsensi')->name('absensi.detail');    // ➜ baru, semua data
    Route::get('grafik/detail-absensi', 'grafikDetailAbsensi')->name('grafik.detail');
    Route::get('grafik/detail-situs', 'grafikDetailSitus')->name('grafik.diagram_detail');
    Route::get('grafik/daily-detail', 'grafikDailyDetail')->name('grafik.daily_detail');
    Route::get('grafik/perbandingan', 'grafikPerbandingan')->name('grafik.perbandingan');

    Route::get('grafik/compare-data', 'grafikCompareData')->name('grafik.compare_data'); // 🔹 baru
});


/* =========================
   Absensi
   ========================= */
Route::prefix('formauditor')->middleware('auth')->group(function () {
    Route::get('/absensi', [AbsensiController::class, 'index'])->name('formauditor.absensi');
});


   /* =========================
   Master: Situs
   ========================= */
Route::prefix('daftarsitus')->as('daftarsitus.')->controller(SitusController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/new', 'new')->name('new');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/save', 'store')->name('save');
    Route::post('/update', 'update')->name('update');
    Route::delete('/remove', 'destroy')->name('destroy');
});

/* =========================
   Master: Jabatan
   ========================= */
Route::prefix('daftarjabatan')->as('daftarjabatan.')->controller(JabatanController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/new', 'new')->name('new');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/save', 'store')->name('save');
    Route::post('/update', 'update')->name('update');
    Route::delete('/remove', 'destroy')->name('destroy');
});

/* =========================
   Master: User
   ========================= */
Route::prefix('daftaruser')->as('daftaruser.')->controller(UserController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/new', 'new')->name('new');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::get('/details/{id}', 'details')->name('details');
    Route::post('/updatepassword', 'updatePassword')->name('updatepassword');
    Route::post('/save', 'store')->name('save');
    Route::post('/update', 'update')->name('update');
    Route::delete('/remove', 'destroy')->name('destroy');
    Route::get('/load/{id}', 'load')->name('load');
    Route::post('/privilege', 'privileges')->name('privilege');
});

/* =========================
   Password
   ========================= */
Route::prefix('password')->as('password.')->controller(UserController::class)->group(function () {
    Route::get('/', 'updatePasswordForm')->name('update');
});

Route::prefix('resetpassword')->as('resetpassword.')->controller(UserController::class)->group(function () {
    Route::get('/', 'resetPasswordForm')->name('resetpwd');
    Route::post('/resetpassword', 'resetPassword')->name('resetpassword');
});

/* =========================
   HS Forum (Umum)
   ========================= */
Route::prefix('hsforum')->as('hsforum.')->controller(AuditFormController::class)->group(function () {
    Route::get('/',                       'index')->name('index');
    Route::get('/opencases',              'opencases')->name('opencases');
    Route::get('/pendingcases',           'pendingcases')->name('pendingcases');
    Route::get('/onprogresscases',        'onprogresscases')->name('onprogresscases');
    Route::get('/onprogressnoguilt',      'onprogressnoguilt')->name('onprogressnoguilt');
    Route::get('/onprogressguilt',        'onprogressguilt')->name('onprogressguilt');
    Route::get('/closedcasesnoguilt',     'closedcasenoguilt')->name('closedcasesnoguilt');      // (method name asli dipertahankan)
    Route::get('/closedcaseslowguilt',    'closedcaselowguilt')->name('closedcaseslowguilt');
    Route::get('/closedcasesmediumguilt', 'closedcasemediumguilt')->name('closedcasesmediumguilt');
    Route::get('/closedcaseshighguilt',   'closedcasehighguilt')->name('closedcaseshighguilt');  // (method name asli dipertahankan)
    Route::get('/post/{slug}',            'post')->name('post');
    Route::get('/postdetails/{slug}',     'postdetails')->name('postdetails');
    Route::post('/save',                  'store')->name('save');
    Route::post('/update',                'update')->name('update');
    Route::delete('/remove',              'destroy')->name('destroy');
});

/* =========================
   HS Forum (OP)
   ========================= */
Route::prefix('opforum')->as('opforum.')->controller(AuditFormController::class)->group(function () {
    Route::get('/',                       'indexOP')->name('indexop');
    Route::get('/opencases',              'opencasesOP')->name('opencases');
    Route::get('/pendingcases',           'pendingcasesOP')->name('pendingcases');
    Route::get('/onprogresscases',        'onprogresscasesOP')->name('onprogresscases');
    Route::get('/onprogressnoguilt',      'onprogressnoguiltOP')->name('onprogressnoguilt');
    Route::get('/onprogressguilt',        'onprogressguiltOP')->name('onprogressguilt');
    Route::get('/closedcasesnoguilt',     'closedcasenoguiltOP')->name('closedcasesnoguilt');
    Route::get('/closedcaseslowguilt',    'closedcaselowguiltOP')->name('closedcaseslowguilt');
    Route::get('/closedcasesmediumguilt', 'closedcasemediumguiltOP')->name('closedcasesmediumguilt');
    Route::get('/closedcaseshighguilt',   'closedcasehighguiltOP')->name('closedcaseshighguilt');
    Route::get('/oppost/{slug}',          'postOP')->name('post');
    Route::get('/oppostdetails/{slug}',   'postdetailsOP')->name('postdetails');

    Route::get('/opabsensi',              'opabsensi')->name('opabsensi');
    Route::get('/opabsensi/users',        'opAbsensiUsers')->name('opabsensi.users');
    Route::get('/opabsensi/staff-by-site','opabsensiStaff')->name('opabsensi.staff');
    Route::get('/opabsensi-detail', 'opabsensiDetail')->name('opabsensi.detail');
});

/* =========================
   HS Forum (Auditor)
   ========================= */
Route::prefix('auditorforum')->as('auditorforum.')->controller(AuditFormController::class)->group(function () {
    Route::get('/',                              'new')->name('new');
    Route::get('/auditoropencases',              'auditoropencases')->name('auditoropencases');
    Route::get('/auditoronprogresscases',        'auditoronprogresscases')->name('auditoronprogresscases');
    Route::get('/auditoronprogressnoguiltcases', 'auditoronprogressnoguiltcases')->name('auditoronprogressnoguiltcases');
    Route::get('/auditoronprogressguiltcases',   'auditoronprogressguiltcases')->name('auditoronprogressguiltcases');
    Route::get('/auditoronpendingcases',         'auditoronpendingcases')->name('auditoronpendingcases');
    Route::get('/auditorclosedcasesnoguilt',     'auditorclosedcasesnoguilt')->name('auditorclosedcasesnoguilt');
    Route::get('/auditorclosedcaseslowguilt',    'auditorclosedcaseslowguilt')->name('auditorclosedcaseslowguilt');
    Route::get('/auditorclosedcasesmediumguilt', 'auditorclosedcasesmediumguilt')->name('auditorclosedcasesmediumguilt');
    Route::get('/auditorclosedcaseshighguilt',   'auditorclosedcaseshighguilt')->name('auditorclosedcaseshighguilt');

    // DETAIL + NAMED ROUTE yang dipakai tombol Comment
    Route::get('/auditorpost/{slug}',            'auditorpost')->name('auditorpost');
    Route::get('/auditorpostdetails/{slug}',     'auditorpostdetails')->name('auditorpostdetails');

    Route::get('/auditorclosedcasespriode',      'auditorclosedcasespriode')->name('auditorclosedcasespriode');
    Route::post('/recovery/{slug}',              'recovery')->name('recovery');

    /* -----------------------------------------------------------------
       ⬇⬇ Tambahan route halaman "Comments" (controller terpisah & bersih)
       URL: /auditorforum/{slug}/comments
       Name: auditorforum.comments
       ----------------------------------------------------------------- */
    Route::get('/{slug}/comments', [HsForumController::class, 'comments'])->name('comments');
});

/* ======================================================================
   🔗 Alias route detail "HS Forum (Auditor)" – opsional, kompatibel lama
   URL: /auditorforum/auditorpostdetails/{slug}
   ====================================================================== */
Route::get(
    '/auditorforum/auditorpostdetails/{slug}',
    [AuditFormController::class, 'auditorpostdetails']
)->name('auditorforum.auditorpostdetails');

/* =========================
   Old Periode / Old Data
   ========================= */
Route::prefix('oldperiode')->as('oldperiode.')->group(function () {
    Route::get('/', [AuditFormController::class, 'auditorclosedcasesreport'])->name('auditorclosedcasesreport');
    Route::get('/auditorpostdetailsreport/{slug}', [OlddataController::class, 'auditorpostdetailsreport'])->name('auditorpostdetailsreport');
});

Route::prefix('olddata')->as('olddata.')->controller(OlddataController::class)->group(function () {
    Route::get('/', 'olddataclosedcases')->name('olddataclosedcases');
    Route::get('/olddatadetails/{slug}', 'olddatadetails')->name('olddatadetails');
    Route::get('/olddatanoguilt', 'olddatanoguilt')->name('olddatanoguilt');
    Route::get('/olddatalowguilt', 'olddatalowguilt')->name('olddatalowguilt');
    Route::get('/olddatamediumguilt', 'olddatamediumguilt')->name('olddatamediumguilt');
    Route::get('/olddatahighguilt', 'olddatahighguilt')->name('olddatahighguilt');
});

/* =========================
   Reporting & Setting
   ========================= */
Route::prefix('periodessetting')->as('periodessetting.')->controller(ReportingController::class)->group(function () {
    Route::get('/', 'setting')->name('setting');
    Route::get('/new', 'new')->name('new');
    Route::get('/edit/{id}', 'edit')->name('edit');
    Route::post('/save', 'store')->name('save');
    Route::put('/update/{id}', 'update')->name('update');
    Route::delete('/remove', 'destroy')->name('destroy');
});

Route::prefix('reporting')->as('reporting.')->controller(ReportingController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/get-tahun', 'getTahun')->name('get-tahun');
    Route::get('/get-periode/{tahun}', 'getPeriodeByTahun')->name('get-periode');
    Route::post('/generate-report', 'generateReport')->name('generate-report');
    Route::get('/export', 'exportReport')->name('export');
});

Route::prefix('setting')->as('setting.')->controller(SettingController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/save', 'store')->name('save');
    Route::post('/update', 'update')->name('update');
});

/* =========================
   Migrasi DB
   ========================= */
Route::prefix('migrasidb')->as('migrasidb.')->controller(MigratedbController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/update', 'update')->name('update');
    Route::post('/updateauditor', 'updateAuditor')->name('updateauditor');
});

/* =========================
   Other (Lookup)
   ========================= */
Route::prefix('other')->as('other.')->controller(OtherController::class)->group(function () {
    Route::get('/getposisi/{id}', 'getPosisi')->name('getposisi');
    Route::get('/getjabatan/{id}', 'getJabatan')->name('getjabatan');
    Route::get('/getposisijabatan/{id}', 'getPosisiJabatan')->name('getposisijabatan');
    Route::get('/getnamastaff/{id}', 'getNamaStaff')->name('getnamastaff');
    Route::get('/getnamasitus/{id}', 'getNamaSitus')->name('getnamasitus');
});

/* =========================
   Topik (protected)
   ========================= */
Route::middleware('auth')->prefix('dashboard')->group(function () {
    Route::get('topik',                         [TopikController::class, 'index'])->name('topik.index');
    Route::get('topik/create',                  [TopikController::class, 'create'])->name('topik.create');
    Route::post('topik',                        [TopikController::class, 'store'])->name('topik.store');
    Route::get('topik/{topik}/edit',            [TopikController::class, 'edit'])->name('topik.edit');
    Route::put('topik/{topik}',                 [TopikController::class, 'update'])->name('topik.update');
    Route::delete('topik/{topik}',              [TopikController::class, 'destroy'])->name('topik.destroy');
    Route::patch('topik/{topik}/toggle-status', [TopikController::class, 'toggleStatus'])->name('topik.toggle');

    // 🔎 AJAX: cek nama topik duplikat
    Route::get('topik/check',                   [TopikController::class, 'check'])->name('topik.check');

    // 👁️ Detail topik (diletakkan paling akhir agar tidak bentrok dengan "topik/check")
    Route::get('topik/{topik}',                 [TopikController::class, 'show'])->name('topik.show');
});
