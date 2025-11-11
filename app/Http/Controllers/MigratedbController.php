<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\User;
use App\Models\Forumaudit;
use App\Models\Forumauditpost;
use Illuminate\Http\Request;
use App\Helpers\AuthLink;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MigratedbController extends Controller
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

    public function index(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $datastaffs = User::pluck('id_admin', 'id_admin');
        $dataauditors = User::where('id_jabatan',14)->pluck('id_admin','id_admin');
        return view('pages.migratedb')->with([
            'datastaffs' => $datastaffs,
            'dataauditors' => $dataauditors
        ]);
    }

    public function update(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make( $input, [
            'idadmin_dari' => 'required',
            'idadmin_ke' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $staffdr = User::where('id_admin',$request->idadmin_dari)->first();
        $staffke = User::where('id_admin',$request->idadmin_ke)->first();
        $dbtoupdate = Forumaudit::where('created_for',$request->idadmin_dari)->where('created_for_name',$staffke->nama_staff)->update(['created_for' => $request->idadmin_ke]);
        $dbpostupdate = Forumauditpost::where('updated_by',$request->idadmin_dari)->where('updated_by_name',$staffke->nama_staff)->update(['updated_by' => $request->idadmin_ke]);

        return ($dbtoupdate && $dbpostupdate) ? redirect()->route('migrasidb.index')->with('success','Data berhasil diupdate') : redirect()->back()->with('danger','Gagal update! Nama User harus sama!!!');
    }

    public function updateAuditor(Request $request)
    {
        // Validate the request input
        $validated = $request->validate([
            'idauditor_dari' => 'required',
            'idauditor_ke'   => 'required',
        ]);

        // Fetch the source and target auditors
        $auditorFrom = User::where('id_admin', $validated['idauditor_dari'])->first();
        $auditorTo   = User::where('id_admin', $validated['idauditor_ke'])->first();

        // Ensure both users exist
        if (!$auditorFrom || !$auditorTo) {
            return back()->with('danger', 'Auditor tidak ditemukan.');
        }

        $dbauditupdate = Forumaudit::where('created_by',$validated['idauditor_dari'])
                        ->update([
                            'created_by' => $validated['idauditor_ke']
                        ]);

        return $dbauditupdate ? redirect()->route('migrasidb.index')->with('success','Data auditor berhasil diupdate') : redirect()->back()->with('danger','Gagal update! ID Auditor harus sama!!!');
   }
}
