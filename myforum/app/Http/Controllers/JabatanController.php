<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\Posisi;
use Illuminate\Http\Request;
use App\Helpers\AuthLink;
use Yajra\DataTables\DataTables;
use Yajra\DataTables\Html\Builder;
use Illuminate\Support\Facades\Validator;

class JabatanController extends Controller
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

    /**
     * Display a listing of the resource.
     */
    public function index(Builder $builder, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        if (request()->ajax()) {
            return DataTables::of(Jabatan::orderBy('bagian_posisi','ASC')->orderBy('nama_jabatan','ASC'))
                ->addIndexColumn()
                ->addColumn('bagian_posisi', function ($model) {
                    if ($model->bagian_posisi == 1) {
                        return "Operasional";
                    } elseif($model->bagian_posisi == 2) {
                        return "Marketing";
                    } else {
                        return "Support";
                    }
                })
                ->addColumn('action',function($model) {
                    return '
                        <a href="' . route('daftarjabatan.edit',$model->id). '" class="btn btn-warning btn-icon edit">
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
                'data' => 'bagian_posisi',
                'name' => 'bagian_posisi',
                'title' => 'Posisi Bagian'
            ],
            [
                'data' => 'nama_jabatan',
                'name' => 'nama_jabatan',
                'title' => 'Nama Jabatan'
            ],
            [
                'data' => 'action', 'title' => 'Action', 'class' => 'text-center',
                'orderable' => false, 'searchable' => false,
                'width' => '120px'
            ]
        ]);

        return view('pages.jabatan.index', compact('html'));
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

        $positions = Posisi::pluck('nama_posisi', 'id');
        return view('pages.jabatan.new')->with([
            "positions" => $positions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_jabatan' => 'required',
            'bagian_posisi' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
        }

        $store = Jabatan::create($request->all());

        return $store ? redirect()->route('daftarjabatan.index')->with('success','Data berhasil disimpan') : redirect()->back()->with('danger','Gagal disimpan! Silakan hubungi Administrator');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $daftarjabatan = Jabatan::where('id', $id)->firstOrFail();
        $positions = Posisi::pluck('nama_posisi', 'id');
        return view('pages.jabatan.edit')->with([
            "daftarjabatan" => $daftarjabatan,
            "positions" => $positions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make( $input, [
            'nama_jabatan' => 'required',
            'bagian_posisi' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $daftarjabatan = Jabatan::find($input['id']);
        $daftarjabatan->nama_jabatan = $input['nama_jabatan'];
        $daftarjabatan->bagian_posisi = $input['bagian_posisi'];

        $store = $daftarjabatan->save();

        return $store ? redirect()->route('daftarjabatan.index')->with('success','Data berhasil disimpan') : redirect()->back()->with('danger','Gagal update! Silakan hubungi Administrator');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $daftarjabatan = Jabatan::where('id', $request->id)->firstOrFail();
        $destroy = $daftarjabatan->delete();

        return $destroy ? response()->json(['success' => true, 'message' => 'Data berhasil dihapus']) : response()->json(['success' => false, 'message' => 'Gagal dihapus! Silakan hubungi Administrator']);
    }
}
