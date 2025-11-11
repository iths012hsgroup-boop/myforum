<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Posisi;
use App\Models\Jabatan;
use App\Models\Daftarsitus;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OtherController extends Controller
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

    public function getPosisi($id)
    {
        $data = Posisi::find($id);
        return response()->json($data);
    }

    public function getJabatan($id)
    {
        $data = Jabatan::find($id);
        return response()->json($data);
    }

    public function getPosisiJabatan($id)
    {
        $data = Jabatan::where('bagian_posisi', $id)->get();
        return response()->json($data);
    }

    public function getNamaStaff($id)
    {
        $data = User::where('id_admin', $id)->get();
        return response()->json($data);
    }

    public function getNamaSitus($id)
    {
        $datauser = User::where('id_admin', $id)->first();

        if (!$datauser) {
            return response()->json(['error' => 'User tidak ditemukan'], 404);
        }

        $idSitusArray = explode(',', $datauser->id_situs);

        $data = Daftarsitus::whereIn('id', $idSitusArray)
            ->pluck('nama_situs', 'id');

        return response()->json($data);
    }
}
