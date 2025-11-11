<?php

namespace App\Http\Controllers;

use App\Models\Forumaudit;
use App\Models\SettingPeriode;
use Illuminate\Http\Request;
use App\Helpers\AuthLink;
use App\Models\Forumauditpost;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\Html\Builder;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class OlddataController extends Controller
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

     public function olddataclosedcases(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $tahunSekarang = now()->year;
        $currentPeriodeRecord = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->orderBy('periode', 'desc')->first();
        $currentPeriode = $currentPeriodeRecord ? $currentPeriodeRecord->periode : null;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        $case_closed_aman = \App\Models\Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','4')->where('status_kesalahan','1')->where('periode', '<>', $currentPeriode)->where('soft_delete','0')->count();
        $case_closed_bersalah_low = \App\Models\Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','4')->where('status_kesalahan','2')->where('periode', '<>', $currentPeriode)->where('soft_delete','0')->count();
        $case_closed_bersalah_medium  = \App\Models\Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','4')->where('status_kesalahan','3')->where('periode', '<>', $currentPeriode)->where('soft_delete','0')->count();
        $case_closed_bersalah_high = \App\Models\Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','4')->where('status_kesalahan','4')->where('periode', '<>', $currentPeriode)->where('soft_delete','0')->count();

        $periodes = SettingPeriode::all();
        return view('pages.formauditor.olddata')->with([
            'case_closed_aman' =>  $case_closed_aman,
            'case_closed_bersalah_low' =>  $case_closed_bersalah_low,
            'case_closed_bersalah_medium' =>  $case_closed_bersalah_medium,
            'case_closed_bersalah_high' =>  $case_closed_bersalah_high,
            ]);
    }

    public function olddatanoguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $tahunSekarang = now()->year;
        $currentPeriodeRecord = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->orderBy('periode', 'desc')->first();
        $currentPeriode = $currentPeriodeRecord ? $currentPeriodeRecord->periode : null;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            $query = Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','4')->where('status_kesalahan','1')->where('periode', '<>', $currentPeriode)->where('soft_delete','0');
            if ($request->filled('periode')) {
                $tahun = substr($request->periode, 0, 4);
                $periode = substr($request->periode, 4, 1);
                $query->whereRaw('LEFT(periode, 4) = ? AND SUBSTRING(periode, 5, 1) = ?', [$tahun, $periode]);
            }
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '
                        <img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    if ($model->status_case == 1) {
                        return "Open";
                    } else if ($model->status_case == 2) {
                        return "On Progress";
                    } else if ($model->status_case == 3) {
                        return "Pending";
                    } else {
                        return "Closed";
                    }
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
                'data' => 'status_case', 'class' => 'text-center',
                'name' => 'status_case',
                'title' => 'Status Topik',
                'width' => '180px'
            ],
            [
                'data' => 'created_by',
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
            'paging' => true,
            'info' => true,
            'lengthMenu' => [[10, 25, 50, 100, 250, 500, -1], [10, 25, 50, 100, 250, 500, "Show all"]],
            'pageLength' => 10,
        ]);

        return view('pages.formauditor.olddatanoguilt', compact('html'));
    }

    public function olddatalowguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $tahunSekarang = now()->year;
        $currentPeriodeRecord = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->orderBy('periode', 'desc')->first();
        $currentPeriode = $currentPeriodeRecord ? $currentPeriodeRecord->periode : null;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            $query = Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','4')->where('status_kesalahan','2')->where('periode', '<>', $currentPeriode)->where('soft_delete','0');
            if ($request->filled('periode')) {
                $tahun = substr($request->periode, 0, 4);
                $periode = substr($request->periode, 4, 1);
                $query->whereRaw('LEFT(periode, 4) = ? AND SUBSTRING(periode, 5, 1) = ?', [$tahun, $periode]);
            }
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '
                        <img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    if ($model->status_case == 1) {
                        return "Open";
                    } else if ($model->status_case == 2) {
                        return "On Progress";
                    } else if ($model->status_case == 3) {
                        return "Pending";
                    } else {
                        return "Closed";
                    }
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('olddata.olddatadetails',$model->slug). '" class="btn btn-info btn-icon edit">
                                    <i class="fas fa-info-circle mg-r-0"> Details</i>
                                </a>
                            ';
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
                'data' => 'status_case', 'class' => 'text-center',
                'name' => 'status_case',
                'title' => 'Status Topik',
                'width' => '180px'
            ],
            [
                'data' => 'created_by',
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
            'paging' => true,
            'info' => true,
            'lengthMenu' => [[10, 25, 50, 100, 250, 500, -1], [10, 25, 50, 100, 250, 500, "Show all"]],
            'pageLength' => 10,
        ]);

        return view('pages.formauditor.olddatalowguilt', compact('html'));
    }

    public function olddatamediumguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $tahunSekarang = now()->year;
        $currentPeriodeRecord = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->orderBy('periode', 'desc')->first();
        $currentPeriode = $currentPeriodeRecord ? $currentPeriodeRecord->periode : null;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            $query =Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','4')->where('status_kesalahan','3')->where('periode', '<>', $currentPeriode)->where('soft_delete','0');
            if ($request->filled('periode')) {
                $tahun = substr($request->periode, 0, 4);
                $periode = substr($request->periode, 4, 1);
                $query->whereRaw('LEFT(periode, 4) = ? AND SUBSTRING(periode, 5, 1) = ?', [$tahun, $periode]);
            }
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '
                        <img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    if ($model->status_case == 1) {
                        return "Open";
                    } else if ($model->status_case == 2) {
                        return "On Progress";
                    } else if ($model->status_case == 3) {
                        return "Pending";
                    } else {
                        return "Closed";
                    }
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('olddata.olddatadetails',$model->slug). '" class="btn btn-info btn-icon edit">
                                    <i class="fas fa-info-circle mg-r-0"> Details</i>
                                </a>
                            ';
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
                'data' => 'status_case', 'class' => 'text-center',
                'name' => 'status_case',
                'title' => 'Status Topik',
                'width' => '180px'
            ],
            [
                'data' => 'created_by',
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
            'paging' => true,
            'info' => true,
            'lengthMenu' => [[10, 25, 50, 100, 250, 500, -1], [10, 25, 50, 100, 250, 500, "Show all"]],
            'pageLength' => 10,
        ]);

        return view('pages.formauditor.olddatamediumguilt', compact('html'));
    }

    public function olddatahighguilt(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }
        $tahunSekarang = now()->year;
        $currentPeriodeRecord = \App\Models\Forumaudit::whereRaw('LEFT(periode, 4) = ?', [$tahunSekarang])->orderBy('periode', 'desc')->first();
        $currentPeriode = $currentPeriodeRecord ? $currentPeriodeRecord->periode : null;
        //status case 1=open, 2=on progress, 3=pending, 4=close
        //status kesalahan 1=tidak bersalah, 2=bersalah low, 3=bersalah medium, 4=bersalah high
        if (request()->ajax()) {
            $query =Forumaudit::where('created_for', auth()->user()->id_admin)->where('created_for_name', auth()->user()->nama_staff)->where('status_case','4')->where('status_kesalahan','4')->where('periode', '<>', $currentPeriode)->where('soft_delete','0');
            if ($request->filled('periode')) {
                $tahun = substr($request->periode, 0, 4);
                $periode = substr($request->periode, 4, 1);
                $query->whereRaw('LEFT(periode, 4) = ? AND SUBSTRING(periode, 5, 1) = ?', [$tahun, $periode]);
            }
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('link_gambar', function ($model) {
                    return '
                        <img src="' .Storage::url($model->link_gambar). '" class="img-responsive" style="width : 200px"/>
                    ';
                })
                ->addColumn('created_at', function ($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('status_case', function ($model) {
                    if ($model->status_case == 1) {
                        return "Open";
                    } else if ($model->status_case == 2) {
                        return "On Progress";
                    } else if ($model->status_case == 3) {
                        return "Pending";
                    } else {
                        return "Closed";
                    }
                })
                ->addColumn('action', function ($model) {
                        return '
                                <a href="' . route('olddata.olddatadetails',$model->slug). '" class="btn btn-info btn-icon edit">
                                    <i class="fas fa-info-circle mg-r-0"> Details</i>
                                </a>
                            ';
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
                'data' => 'status_case', 'class' => 'text-center',
                'name' => 'status_case',
                'title' => 'Status Topik',
                'width' => '180px'
            ],
            [
                'data' => 'created_by',
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
            'paging' => true,
            'info' => true,
            'lengthMenu' => [[10, 25, 50, 100, 250, 500, -1], [10, 25, 50, 100, 250, 500, "Show all"]],
            'pageLength' => 10,
        ]);

        return view('pages.formauditor.olddatahighguilt', compact('html'));
    }

        /**
     * Show the details.
     */
    public function olddatadetails($slug, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $datadetailforumaudit = Forumaudit::where('slug', $slug)->firstOrFail();
        $datadetailforumauditpost = Forumauditpost::where('slug',$datadetailforumaudit->slug)->where('parent_forum_id',$datadetailforumaudit->id)->where('parent_case_id',$datadetailforumaudit->case_id)->orderBy('id','ASC')->get();

        return view('pages.formauditor.olddatadetails')->with([
            "datadetailforumaudit" => $datadetailforumaudit,
            "datadetailforumauditpost" => $datadetailforumauditpost,
        ]);
    }

}