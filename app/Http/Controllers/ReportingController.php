<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\SettingPeriode;
use App\Models\Reporting;
use App\Models\Forumaudit;
use App\Helpers\AuthLink;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\Html\Builder;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportingExport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ReportingController extends Controller
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
    
    public function index(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        if ($request->ajax()) {
            $query = Reporting::query();

            if ($request->filled('search_keywords')) {
                $keywords = explode(',', $request->search_keywords);
            
                $query->orWhere(function($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $keyword = trim($keyword);
                        $q->orWhere(function($subQ) use ($keyword) {
                            $subQ->orWhereRaw('POSITION(? IN id_staff) > 0', [$keyword])->orWhereRaw('POSITION(? IN site_situs) > 0', [$keyword])->orWhereRaw('POSITION(? IN nama_staff) > 0', [$keyword]);
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
        
            return DataTables::of($query)->addIndexColumn()->make(true);
        }

        $html = $builder->columns([
            [
                'data' => 'DT_RowIndex', 'title' => '#',
                'orderable' => false, 'searchable' => false,
                'width' => '24px'
            ],
            [
                'data' => 'id_staff',
                'name' => 'id_staff',
                'title' => 'ID Staff'
            ],
            [
                'data' => 'nama_staff',
                'name' => 'nama_staff',
                'title' => 'Nama Staff'
            ],
            [
                'data' => 'periode', 'class' => 'text-center',
                'name' => 'periode',
                'title' => 'Periode'
            ],
            [
                'data' => 'site_situs', 'class' => 'text-center',
                'name' => 'site_situs',
                'title' => 'Situs'
            ],
            [
                'data' => 'tidak_bersalah', 'class' => 'text-center',
                'name' => 'tidak_bersalah',
                'title' => 'Status Tidak Bersalah'
            ],
            [
                'data' => 'bersalah_low', 'class' => 'text-center',
                'name' => 'bersalah_low',
                'title' => 'Status Bersalah (Low)'
            ],
            [
                'data' => 'bersalah_medium', 'class' => 'text-center',
                'name' => 'bersalah_medium',
                'title' => 'Status Bersalah (Medium)'
            ],
            [
                'data' => 'bersalah_high', 'class' => 'text-center',
                'name' => 'bersalah_high',
                'title' => 'Status Bersalah (High)'
            ],
            [
                'data' => 'total_case', 'class' => 'text-center',
                'name' => 'total_case',
                'title' => 'Total Case'
            ]
        ])->parameters([
            'searching' => false,
            'paging' => true,
            'info' => true,
            'lengthMenu' => [[10, 25, 50, 100, 250, 500, -1], [10, 25, 50, 100, 250, 500, "Show all"]],
            'pageLength' => 10,
        ]);

        return view('pages.reporting.index', compact('html'));
    }
    
    public function getTahun(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $tahun = SettingPeriode::select('tahun')->distinct()->orderBy('tahun', 'desc')->get();
        return response()->json($tahun);
    }

    public function getPeriodeByTahun($tahun, Request $request )
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $periode = SettingPeriode::where('tahun', $tahun)->get();
        return response()->json($periode);
    }

    public function setting(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        if ($request->ajax()) {
            $data = SettingPeriode::all();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($model) {
                        return '
                            <a href="' . route('periodessetting.edit', $model->id) . '" class="btn btn-warning btn-icon edit">
                                <i class="fas fa-pencil-alt mg-r-0"></i>
                            </a>
                            <button type="button" class="btn btn-danger btn-icon delete">
                                <i class="fas fa-trash mg-r-0" style="color : #fff !important"></i>
                            </button>
                        ';
                })
                ->toJson();
        }

        $html = $builder->columns([
            [
                'data' => 'DT_RowIndex', 'title' => '#',
                'orderable' => false, 'searchable' => false,
                'width' => '24px'
            ],
            [
                'data' => 'bulan_dari',
                'name' => 'bulan_dari',
                'title' => 'Bulan Dari'
            ],
            [
                'data' => 'bulan_ke',
                'name' => 'bulan_ke',
                'title' => 'Bulan Ke'
            ],
            [
                'data' => 'tahun', 'class' => 'text-center',
                'name' => 'tahun',
                'title' => 'Tahun'
            ],
            [
                'data' => 'periode', 'class' => 'text-center',
                'name' => 'periode',
                'title' => 'Periode'
            ],
            [
                'data' => 'action', 'title' => 'Action', 'class' => 'text-center',
                'orderable' => false, 'searchable' => false,
                'width' => '120px'
            ]
        ]);

        return view('pages.reporting.settingperiode', compact('html'));
    }

    public function new(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        return view('pages.reporting.newperiode');
    }

    public function edit($id, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $periode = SettingPeriode::find($id);

        if (!$periode) {
            return redirect()->route('periodessetting.setting')->with('error', 'Data tidak ditemukan!');
        }
        return view('pages.reporting.editperiode', compact('periode'));
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'bulan_dari' => 'required|date_format:Y-m-d H:i:s',
            'bulan_ke' => 'required|date_format:Y-m-d H:i:s',
            'tahun' => 'required|digits:4',
            'periode' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $settingPeriode = new SettingPeriode;
        $settingPeriode->bulan_dari = $request->bulan_dari;
        $settingPeriode->bulan_ke = $request->bulan_ke;
        $settingPeriode->tahun = $request->tahun;
        $settingPeriode->periode = $request->periode;

        $store = $settingPeriode->save();

        return $store ? redirect()->route('periodessetting.setting')->with('success', 'Data berhasil disimpan') : redirect()->back()->with('danger', 'Gagal simpan! Silakan hubungi Administrator');
    }

    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'bulan_dari' => 'required|date_format:Y-m-d H:i:s',
            'bulan_ke' => 'required|date_format:Y-m-d H:i:s',
            'tahun' => 'required|digits:4',
            'periode' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $settingPeriode = SettingPeriode::find($id);
        if (!$settingPeriode) {
            return redirect()->route('periodessetting.setting')->with('error', 'Data tidak ada!');
        }

        $settingPeriode->bulan_dari = $request->bulan_dari;
        $settingPeriode->bulan_ke = $request->bulan_ke;
        $settingPeriode->tahun = $request->tahun;
        $settingPeriode->periode = $request->periode;

        $store = $settingPeriode->save();

        return $store ? redirect()->route('periodessetting.setting')->with('success', 'Data berhasil dipdate')
            : redirect()->back()->with('danger', 'Gagal update! Silakan hubungi Administrator');
    }


    public function destroy(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $Periode = SettingPeriode::where('id', $request->id)->firstOrFail();
        $destroy = $Periode->delete();

        return $destroy ? response()->json(['success' => true, 'message' => 'Data berhasil dihapus']) : response()->json(['success' => false, 'message' => 'Gagal hapus! Silakan hubungi Administrator']);
    }

    public function generateReport(Request $request)
    {
        
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return response()->json(['success' => false, 'message' => 'Tidak memiliki akses!'], 403);
        }

        $validator = Validator::make($request->all(), [
            'tahun' => 'required|digits:4',
            'periode' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal!', 'errors' => $validator->errors()], 400);
        }

        $tahun = $request->tahun;
        $periode = $request->periode;

        $periodeFilter = $tahun . $periode;

        Reporting::where('periode', $periodeFilter)->delete();

        $dataForum = Forumaudit::where('periode', $periodeFilter)->get();

        if ($dataForum->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan!'], 404);
        }

        try {
            $reportData = $dataForum->groupBy('site_situs')->map(function ($group) {
                return $group->groupBy(function ($item) {
                    return strtolower($item->created_for);
                })->map(function ($subGroup) {
                    $firstItem = $subGroup->first();
                    
                    $tidakBersalah = $subGroup->where('status_kesalahan', 1)->where('status_case', 4)->count();
                    $bersalahLow = $subGroup->where('status_kesalahan', 2)->where('status_case', 4)->count();
                    $bersalahMedium = $subGroup->where('status_kesalahan', 3)->where('status_case', 4)->count();
                    $bersalahHigh = $subGroup->where('status_kesalahan', 4)->where('status_case', 4)->count();
                    
                    $totalCase = $tidakBersalah + $bersalahLow + $bersalahMedium + $bersalahHigh;
    
                    return [
                        'id_staff' => strtolower($firstItem->created_for),
                        'nama_staff' => $firstItem->created_for_name,
                        'site_situs' => $firstItem->site_situs,
                        'periode' => $firstItem->periode,
                        'tidak_bersalah' => $tidakBersalah,
                        'bersalah_low' => $bersalahLow,
                        'bersalah_medium' => $bersalahMedium,
                        'bersalah_high' => $bersalahHigh,
                        'total_case' => $totalCase,
                    ];
                });
            })->flatten(1);
            Reporting::insert($reportData->toArray());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memproses data!'], 500);
        }
        return response()->json(['success' => true, 'message' => 'Laporan berhasil dibuat.']);
    }

    public function exportReport(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return response()->json(['success' => false, 'message' => 'Tidak memiliki akses!'], 403);
        }
        
        $tahun = $request->input('tahun');
        $periode = $request->input('periode');
        $bulanDari = $request->input('bulan_dari');
        $bulanKe = $request->input('bulan_ke');

        if (!$tahun || !$periode || !$bulanDari || !$bulanKe) {
            return response()->json(['success' => false, 'message' => 'Silakan pilih tahun, periode, bulan dari, dan bulan ke terlebih dahulu.'], 400);
        }

        $periodeFilter = $tahun . $periode;
        $data = Reporting::whereRaw('LEFT(periode, ?) = ?', [strlen($periodeFilter), $periodeFilter])
                        ->orderBy('nama_staff')
                        ->orderBy('site_situs')
                        ->get();

        if ($data->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan!'], 404);
        }

        $dateNow = date('Y-m-d');
        $fileName = 'Periode' . $periodeFilter . '-(' . $dateNow . ')' . '.xlsx';
        return Excel::download(new ReportingExport($data), $fileName);
    }

}
