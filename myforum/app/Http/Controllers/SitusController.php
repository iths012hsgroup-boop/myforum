<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\AuthLink;
use App\Models\Daftarsitus;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\Html\Builder;
use Illuminate\Support\Facades\Validator;

class SitusController extends Controller
{
    /**
    * Create a new controller instance.
    *
    * @return void
    */
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

        if (request()->ajax()) {
            return DataTables::of(Daftarsitus::orderBy('id','ASC'))
                ->addIndexColumn()
                ->addColumn('action',function($model) {
                    return '
                        <a href="' . route('daftarsitus.edit',$model->id). '" class="btn btn-warning btn-icon edit">
                            <i class="fas fa-pencil-alt mg-r-0"></i>
                        </a>

                        <button type="button" class="btn btn-danger btn-icon delete">
                            <i class="fas fa-trash mg-r-0" style="color : #fff !important"></i>
                        </button>
                    ';
                })
                ->rawColumns(['action'])
                ->toJson();
        }

        $html = $builder->columns([
            [
                'data' => 'DT_RowIndex', 'title' => '#',
                'orderable' => false, 'searchable' => false,
                'width' => '24px'
            ],
            [
                'data' => 'nama_situs',
                'name' => 'nama_situs',
                'title' => 'Nama Situs'
            ],
            [
                'data' => 'action', 'title' => 'Action', 'class' => 'text-center',
                'orderable' => false, 'searchable' => false,
                'width' => '120px'
            ]
        ]);

        return view('pages.daftarsitus.index', compact('html'));
    }

    public function new(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        return view('pages.daftarsitus.new');
    }

    public function edit($id, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $daftarsitus = Daftarsitus::where('id', $id)->firstOrFail();
        return view('pages.daftarsitus.edit')->with([
            "daftarsitus" => $daftarsitus,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_situs' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
        }

        $store = Daftarsitus::create($request->all());

        return $store ? redirect()->route('daftarsitus.index')->with('success','Data berhasil disimpan') : redirect()->back()->with('danger','Gagal disimpan! Silakan hubungi Administrator');
    }

    public function update(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make( $input, [
            'nama_situs' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $daftarsitus = Daftarsitus::find($input['id']);
        $daftarsitus->nama_situs = $input['nama_situs'];

        $store = $daftarsitus->save();

        return $store ? redirect()->route('daftarsitus.index')->with('success','Data berhasil disimpan') : redirect()->back()->with('danger','Gagal update! Silakan hubungi Administrator');
    }

    public function destroy(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $daftarsitus = Daftarsitus::where('id', $request->id)->firstOrFail();
        $destroy = $daftarsitus->delete();

        return $destroy ? response()->json(['success' => true, 'message' => 'Data berhasil dihapus']) : response()->json(['success' => false, 'message' => 'Gagal dihapus! Silakan hubungi Administrator']);
    }

}
