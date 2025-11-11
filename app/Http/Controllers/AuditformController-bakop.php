<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\AuthLink;
use App\Models\Forumaudit;
use App\Models\Daftarsitus;
use Illuminate\Http\Request;
use App\Helpers\ImagesUpload;
use App\Models\Forumauditpost;
use App\Models\SettingPeriode;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\Html\Builder;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
    public function indexOP(Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $tahunSekarang = now()->year;
        $maxPeriode = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $situs = Daftarsitus::find($user->id_situs);
        $isOperator = $user->id_jabatan == 19;

        $queryBase = \App\Models\Forumaudit::query()->where('soft_delete', '0');

        if ($isOperator) {
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where('id_situs', $situs->id)
                ->pluck('id_admin');

            $queryBase->where('site_situs', $situs->nama_situs)
                    ->whereIn('created_for', $exceptUsers);
        } else {
            $queryBase->where('created_for', $user->id_admin)
                    ->where('created_for_name', $user->nama_staff);
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
        ];

        return view('pages.formauditor.opindex', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function new(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $tahunSekarang = now()->year;
        $maxPeriode = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $case_open = \App\Models\Forumaudit::where('status_case','1')->where('status_kesalahan','0')->where('soft_delete','0')->count();
        $case_progress = \App\Models\Forumaudit::where('status_case','2')->where('status_kesalahan','0')->where('soft_delete','0')->count();
        $case_progress_aman = \App\Models\Forumaudit::where('status_case','2')->where('status_kesalahan','1')->where('soft_delete','0')->count();
        $case_progress_bersalah = \App\Models\Forumaudit::where('status_case','2')->whereIn('status_kesalahan',[2,3,4])->where('soft_delete','0')->count();
        $case_pending = \App\Models\Forumaudit::where('status_case','3')->whereIn('status_kesalahan',[0,1,2,3,4])->where('soft_delete','0')->count();
        $case_closed_aman = \App\Models\Forumaudit::where('status_case','4')->where('status_kesalahan','1')->where('periode', $periodeFilter)->where('soft_delete','0')->count();
        $case_closed_bersalah_low = \App\Models\Forumaudit::where('status_case','4')->where('status_kesalahan','2')->where('periode', $periodeFilter)->where('soft_delete','0')->count();
        $case_closed_bersalah_medium = \App\Models\Forumaudit::where('status_case','4')->where('status_kesalahan','3')->where('periode', $periodeFilter)->where('soft_delete','0')->count();
        $case_closed_bersalah_high = \App\Models\Forumaudit::where('status_case','4')->where('status_kesalahan','4')->where('periode', $periodeFilter)->where('soft_delete','0')->count();

        $datastaffs = User::pluck('id_admin', 'id_admin');
        $periodes = SettingPeriode::all();

        return view('pages.formauditor.new')->with([
            'periodes' =>  $periodes,
            'case_open' =>  $case_open,
            'case_progress' =>  $case_progress,
            'case_progress_aman' => $case_progress_aman,
            'case_progress_bersalah' => $case_progress_bersalah,
            'case_pending' => $case_pending,
            'case_closed_aman' =>  $case_closed_aman,
            'case_closed_bersalah_low' =>  $case_closed_bersalah_low,
            'case_closed_bersalah_medium' =>  $case_closed_bersalah_medium,
            'case_closed_bersalah_high' =>  $case_closed_bersalah_high,
            'datastaffs' => $datastaffs,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'case_id' => 'required|unique:tbhs_forum',
            'topik_title' => 'required',
            'link_gambar' => 'required|file|mimes:jpg,jpeg,png,gif|max:2048',
            'topik_deskripsi' => 'required',
            'created_for' => 'required',
            'created_for_name' => 'required',
            'created_by' => 'required',
            'created_by_name' => 'required',
            'site_situs' => 'required',
            'status_case' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $file = $request->file('link_gambar');

        if (isset($file)) {
            $path = ImagesUpload::upload($request->file('link_gambar'), 'gambar_topik/');
        }

        $store = Forumaudit::create([
            'slug' => substr(str_shuffle(md5(time())),0,25),
            'case_id' => $request->case_id,
            'topik_title' => $request->topik_title,
            'link_gambar' => $request->file('link_gambar') ? $path : null,
            'topik_deskripsi' => $request->topik_deskripsi,
            'created_for' => strtolower($request->created_for),
            'created_for_name' => $request->created_for_name,
            'created_by' => $request->created_by,
            'created_by_name' => $request->created_by_name,
            'site_situs' => $request->site_situs,
            'status_case' => $request->status_case,
            'periode' => $request->periode
        ]);

        return $store ? redirect()->route('auditorforum.new')->with('success', 'Data berhasil disimpan') : redirect()->back()->with('danger', 'Gagal simpan! Silakan hubungi Administrator');
    }

    public function opencasesOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $situs = Daftarsitus::find($user->id_situs);
        $isOperator = $user->id_jabatan == 19;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '1')
            ->where('status_kesalahan', '0')
            ->where('soft_delete', '0');

        if ($isOperator) {
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where('id_situs', $situs->id)
                ->pluck('id_admin');

            $query->where('site_situs', $situs->nama_situs)
                ->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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

    public function onprogresscasesOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $situs = Daftarsitus::find($user->id_situs);
        $isOperator = $user->id_jabatan == 19;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '2')
            ->where('status_kesalahan', '0')
            ->where('soft_delete', '0');

        if ($isOperator) {
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where('id_situs', $situs->id)
                ->pluck('id_admin');

            $query->where('site_situs', $situs->nama_situs)
                ->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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

    public function onprogressnoguiltOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $situs = Daftarsitus::find($user->id_situs);
        $isOperator = $user->id_jabatan == 19;

        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '2')
            ->where('status_kesalahan', '1')
            ->where('soft_delete', '0');

        if ($isOperator) {
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where('id_situs', $situs->id)
                ->pluck('id_admin');

            $query->where('site_situs', $situs->nama_situs)
                ->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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

    public function onprogressguiltOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $situs = Daftarsitus::find($user->id_situs);
        $isOperator = $user->id_jabatan == 19;

        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '2')
            ->whereIn('status_kesalahan', [2, 3, 4])
            ->where('soft_delete', '0');

        if ($isOperator) {
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where('id_situs', $situs->id)
                ->pluck('id_admin');

            $query->where('site_situs', $situs->nama_situs)
                ->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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

    public function pendingcasesOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user ->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $situs = Daftarsitus::find($user->id_situs);
        $isOperator = $user->id_jabatan == 19;

        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '3')
            ->whereIn('status_kesalahan', [0, 1, 2, 3, 4])
            ->where('soft_delete', '0');

        if ($isOperator) {
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where('id_situs', $situs->id)
                ->pluck('id_admin');

            $query->where('site_situs', $situs->nama_situs)
                ->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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

    public function closedcasenoguiltOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $situs = Daftarsitus::find($user->id_situs);
        $isOperator = $user->id_jabatan == 19;

        $tahunSekarang = now()->year;
        $maxPeriode = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;

        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '4')
            ->where('status_kesalahan', '1')
            ->where('periode', $periodeFilter)
            ->where('soft_delete', '0');

        if ($isOperator) {
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where('id_situs', $situs->id)
                ->pluck('id_admin');

            $query->where('site_situs', $situs->nama_situs)
                ->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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

    public function closedcaselowguiltOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $situs = Daftarsitus::find($user->id_situs);
        $isOperator = $user->id_jabatan == 19;

        $tahunSekarang = now()->year;
        $maxPeriode = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '4')
            ->where('status_kesalahan', '2')
            ->where('periode', $periodeFilter)
            ->where('soft_delete', '0');

        if ($isOperator) {
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where('id_situs', $situs->id)
                ->pluck('id_admin');

            $query->where('site_situs', $situs->nama_situs)
                ->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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

    public function closedcasemediumguiltOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $situs = Daftarsitus::find($user->id_situs);
        $isOperator = $user->id_jabatan == 19;

        $tahunSekarang = now()->year;
        $maxPeriode = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '4')
            ->where('status_kesalahan', '3')
            ->where('periode', $periodeFilter)
            ->where('soft_delete', '0');

        if ($isOperator) {
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where('id_situs', $situs->id)
                ->pluck('id_admin');

            $query->where('site_situs', $situs->nama_situs)
                ->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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

    public function closedcasehighguiltOP(Builder $builder, Request $request)
    {
        $user = auth()->user();
        $akses = AuthLink::access_url($user->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $situs = Daftarsitus::find($user->id_situs);
        $isOperator = $user->id_jabatan == 19;

        $tahunSekarang = now()->year;
        $maxPeriode = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $query = Forumaudit::query()
            ->where('status_case', '4')
            ->where('status_kesalahan', '4')
            ->where('periode', $periodeFilter)
            ->where('soft_delete', '0');

        if ($isOperator) {
            $exceptUsers = User::whereIn('id_jabatan', [6, 7, 8, 19, 20])
                ->where('id_situs', $situs->id)
                ->pluck('id_admin');

            $query->where('site_situs', $situs->nama_situs)
                ->whereIn('created_for', $exceptUsers);
        } else {
            $query->where('created_for', $user->id_admin)
                ->where('created_for_name', $user->nama_staff);
        }

        if (request()->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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
        $maxPeriode = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;

        $hasRecoveryAccess = \App\Models\Privilegeaccess::where('id_admin', auth()->user()->id_admin)
        ->where('menu_id', 'HSF015')
        ->exists();

        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('status_case','4')->where('status_kesalahan','1')->where('periode', $periodeFilter)->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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
        $maxPeriode = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high

        $hasRecoveryAccess = \App\Models\Privilegeaccess::where('id_admin', auth()->user()->id_admin)
        ->where('menu_id', 'HSF015')
        ->exists();

        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('status_case','4')->where('status_kesalahan','2')->where('periode', $periodeFilter)->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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
        $maxPeriode = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high

        $hasRecoveryAccess = \App\Models\Privilegeaccess::where('id_admin', auth()->user()->id_admin)
        ->where('menu_id', 'HSF015')
        ->exists();

        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('status_case','4')->where('status_kesalahan','3')->where('periode', $periodeFilter)->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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
        $maxPeriode = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->selectRaw('MAX(CAST(SUBSTRING(periode, 5, 1) AS UNSIGNED)) as max_digit')->pluck('max_digit')->first();
        $periodeFilter = $tahunSekarang . $maxPeriode;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high

        $hasRecoveryAccess = \App\Models\Privilegeaccess::where('id_admin', auth()->user()->id_admin)
        ->where('menu_id', 'HSF015')
        ->exists();

        if (request()->ajax()) {
            return DataTables::of(Forumaudit::where('status_case','4')->where('status_kesalahan','4')->where('periode', $periodeFilter)->where('soft_delete','0'))
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '<img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>';
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
        $currentPeriodeRecord = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->orderBy('periode', 'desc')->first();
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
                    return '<img src="' . Storage::url($model->link_gambar) . '" class="img-responsive" style="width : 200px"/>';
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
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'case_id', 'name' => 'case_id', 'title' => 'Topik ID', 'width' => '100px'],
            ['data' => 'topik_title', 'name' => 'topik_title', 'title' => 'Topik', 'width' => '250px'],
            ['data' => 'site_situs', 'name' => 'site_situs', 'title' => 'Situs', 'width' => '100px'],
            ['data' => 'created_for', 'name' => 'created_for', 'title' => 'ID Staff', 'width' => '100px'],
            ['data' => 'link_gambar', 'name' => 'link_gambar', 'title' => 'Gambaran Topik', 'width' => '200px'],
            ['data' => 'created_at', 'class' => 'text-center', 'name' => 'created_at', 'title' => 'Tanggal Topik', 'width' => '180px'],
            ['data' => 'periode', 'class' => 'text-center', 'name' => 'periode', 'title' => 'Periode Topik', 'width' => '180px'],
            ['data' => 'status_kesalahan', 'class' => 'text-center', 'name' => 'status_kesalahan', 'title' => 'Status Kesalahan', 'width' => '180px'],
            ['data' => 'created_by', 'class' => 'text-center', 'name' => 'created_by', 'title' => 'ID Auditor', 'width' => '100px'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
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
    public function post($slug, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $periodes = SettingPeriode::all();
        $dataforumaudit = Forumaudit::where('slug', $slug)->firstOrFail();
        $dataforumauditpost = Forumauditpost::where('slug',$dataforumaudit->slug)->where('parent_forum_id',$dataforumaudit->id)->where('parent_case_id',$dataforumaudit->case_id)->orderBy('id','ASC')->get();

        return view('pages.formauditor.posting')->with([
            "dataforumaudit" => $dataforumaudit,
            "dataforumauditpost" => $dataforumauditpost,
            "periodes" => $periodes,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function post($slug, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $periodes = SettingPeriode::all();
        $dataforumaudit = Forumaudit::where('slug', $slug)->firstOrFail();
        $dataforumauditpost = Forumauditpost::where('slug',$dataforumaudit->slug)->where('parent_forum_id',$dataforumaudit->id)->where('parent_case_id',$dataforumaudit->case_id)->orderBy('id','ASC')->get();

        return view('pages.formauditor.posting')->with([
            "dataforumaudit" => $dataforumaudit,
            "dataforumauditpost" => $dataforumauditpost,
            "periodes" => $periodes,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function auditorpost($slug, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $periodes = SettingPeriode::all();
        $dataforumaudit = Forumaudit::where('slug', $slug)->firstOrFail();
        $dataforumauditpost = Forumauditpost::where('slug',$dataforumaudit->slug)->where('parent_forum_id',$dataforumaudit->id)->where('parent_case_id',$dataforumaudit->case_id)->orderBy('id','ASC')->get();

        return view('pages.formauditor.auditorposting')->with([
            "dataforumaudit" => $dataforumaudit,
            "dataforumauditpost" => $dataforumauditpost,
            "periodes" => $periodes,
        ]);
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

        if (!empty($request->status_case)) {
            $forumaudit->status_case = $request->status_case;
        } else {
            if ($forumaudit->status_case = 1) {
                $forumaudit->status_case = 2;
            }else {
                $forumaudit->status_case = $forumaudit->status_case;
            }
        }

        if (!empty($request->status_kesalahan)) {
            $forumaudit->status_kesalahan = $request->status_kesalahan;
        } else {
            $forumaudit->status_kesalahan = $forumaudit->status_kesalahan;
        }

        // $forumaudit->status_case = $request->status_case > 0 ? $request->status_case  : '2' ;
        // $forumaudit->status_kesalahan = $request->status_kesalahan > 0 ? $request->status_kesalahan : '0';

        $id_auditors = \App\Models\User::where('posisi_kerja','3')->where('id_jabatan','14')->where('id_admin',$request->created_for)->count();

        if ($id_auditors == 0) {
            if (auth()->user()->posisi_kerja == 3 && auth()->user()->id_jabatan == 14) {
                if ($request->created_by != auth()->user()->id_admin) {
                    return redirect()->back()->with('danger','Gagal update! Anda bukan Auditor yang bertugas!!!');
                }
            }
        }

        // Update periode
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

        return $store ? redirect()->back()->with('success','Data berhasil disimpan') : redirect()->back()->with('danger','Gagal update! Silakan hubungi Administrator');
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
        $datadetailforumauditpost = Forumauditpost::where('slug',$datadetailforumaudit->slug)->where('parent_forum_id',$datadetailforumaudit->id)->where('parent_case_id',$datadetailforumaudit->case_id)->orderBy('id','ASC')->get();

        return view('pages.formauditor.postdetails')->with([
            "datadetailforumaudit" => $datadetailforumaudit,
            "datadetailforumauditpost" => $datadetailforumauditpost,
        ]);
    }

    /**
     * Show the details.
     */
    public function auditorpostdetails($slug, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $datadetailforumaudit = Forumaudit::where('slug', $slug)->firstOrFail();
        $datadetailforumauditpost = Forumauditpost::where('slug',$datadetailforumaudit->slug)->where('parent_forum_id',$datadetailforumaudit->id)->where('parent_case_id',$datadetailforumaudit->case_id)->orderBy('id','ASC')->get();

        return view('pages.formauditor.auditorpostdetails')->with([
            "datadetailforumaudit" => $datadetailforumaudit,
            "datadetailforumauditpost" => $datadetailforumauditpost,
        ]);
    }

    /**
     * Show the details.
     */
    public function auditorpostdetailsreport($slug, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $datadetailforumaudit = Forumaudit::where('slug', $slug)->firstOrFail();
        $datadetailforumauditpost = Forumauditpost::where('slug',$datadetailforumaudit->slug)->where('parent_forum_id',$datadetailforumaudit->id)->where('parent_case_id',$datadetailforumaudit->case_id)->orderBy('id','ASC')->get();

        return view('pages.reporting.auditorpostdetailsreport')->with([
            "datadetailforumaudit" => $datadetailforumaudit,
            "datadetailforumauditpost" => $datadetailforumauditpost,
        ]);
    }

    public function recovery($slug)
    {
        // Cari entitas berdasarkan slug
        $forum = Forumaudit::where('slug', $slug)->Where('status_case', 4)->first();
        $user = auth()->user()->id_admin;

        // Jika data tidak ditemukan
        if (!$forum) {
            return redirect()->back()->with('error', 'Data tidak ditemukan!');
        }

        try {
            // Update kolom status_case menjadi 3
            $forum->update(['status_case' => 2, 'status_kesalahan' => 0, 'recovery_by' => $user]);

            return redirect()->back()->with('success', 'Data berhasil direcovery!');
        } catch (\Exception $e) {
            // Tangani error selama proses update
            return redirect()->back()->with('error', 'Terjadi kesalahan saat melakukan recovery.');
        }
    }
}
