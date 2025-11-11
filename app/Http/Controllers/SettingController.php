<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Helpers\AuthLink;

class SettingController extends Controller
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
    public function index(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $settings = Setting::get()->first();
            return view('pages.setting')->with([
                'settings' => $settings
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pengumuman' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
        }

        $store = Setting::create($request->all());

        return $store ? redirect()->route('setting.index')->with('success','Data berhasil disimpan') : redirect()->back()->with('danger','Gagal disimpan! Silakan hubungi Administrator');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pengumuman' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
        }

        $settings = Setting::get()->first();
        $settings->pengumuman = $request->pengumuman;

        $store = $settings->save();

        return $store ? redirect()->route('setting.index')->with('success', 'Data berhasil diupdate') : redirect()->back()->with('danger', 'Gagal Update! silakan hubungi Administrator');
    }

}
