<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\Menu;
use App\Models\User;
use App\Models\Posisi;
use App\Models\Jabatan;
use App\Helpers\AuthLink;
use App\Models\Daftarsitus;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Privilegeaccess;
use App\Models\Logposisijabatan;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Builder;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
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

        $users = User::orderBy('id','ASC')
            ->pluck('id_admin', 'id');

        $users_privilege = User::orderBy('id','ASC')
            ->pluck('id_admin', 'id_admin');

        $daftarsitus = Daftarsitus::orderBy('id','asc')->get();
        $daftarmenu = Menu::orderBy('id','asc')->get();

        if (request()->ajax()) {
            return DataTables::of(DB::table('tbhs_users'))
                ->addIndexColumn()
                ->addColumn('status', function ($model) {
                    if ($model->status == 1) {
                        return "Aktif";
                    } else {
                        return "Tidak Aktif";
                    }
                })
                ->addColumn('action', function ($model) {
                    return '
                        <a href="' . route('daftaruser.details', $model->id) . '" class="btn btn-info btn-icon">
                            <i class="fas fa-info-circle mg-r-0"></i>
                        </a>
                        <a href="' . route('daftaruser.edit', $model->id) . '" class="btn btn-warning btn-icon edit">
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
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false, 'width' => '24px'],
            ['data' => 'id_admin', 'name' => 'id_admin', 'title' => 'ID Admin'],
            ['data' => 'nama_staff', 'name' => 'nama_staff', 'title' => 'Nama Staff'],
            ['data' => 'email', 'name' => 'email', 'title' => 'Email Kerja'],
            ['data' => 'tanggal_join', 'class' => 'text-center', 'name' => 'tanggal_join', 'title' => 'Tanggal Bergabung (YYYY-MM-DD)'],
            ['data' => 'status', 'class' => 'text-center', 'name' => 'status', 'title' => 'Status'],
            ['data' => 'action', 'title' => 'Action', 'class' => 'text-center', 'orderable' => false, 'searchable' => false, 'width' => '120px']
        ]);

        return view('pages.daftaruser.index', compact('html'))->with([
            "daftarsitus" => $daftarsitus,
            "users" => $users,
            "users_privilege" => $users_privilege,
            "daftarmenu" => $daftarmenu,
        ]);
    }

    public function new(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $listsituss = Daftarsitus::pluck('nama_situs', 'id');
        $positions = Posisi::pluck('nama_posisi', 'id');
        return view('pages.daftaruser.new')->with([
            'listsituss' => $listsituss,
            'positions' => $positions
        ]);
    }

    public function details($id, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $datastaff = User::where('id', $id)->firstOrFail();
        $idSitusArray = explode(',', $datastaff->id_situs);
        $situs = Daftarsitus::whereIn('id', $idSitusArray)
                ->pluck('nama_situs', 'id')
                ->toArray();

        return view('pages.daftaruser.details')->with([
            'datastaff' => $datastaff,
            'situs' => $situs
        ]);
    }

    public function edit($id, Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $listsituss = Daftarsitus::pluck('nama_situs', 'id');
        $positions = Posisi::pluck('nama_posisi', 'id');
        $jabatans = Jabatan::pluck('nama_jabatan', 'id');
        $datastaff = User::where('id', $id)->firstOrFail();
        return view('pages.daftaruser.edit')
            ->with([
                'datastaff' => $datastaff,
                'listsituss' => $listsituss,
                'positions' => $positions,
                'jabatans' => $jabatans
            ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_admin' => 'required|unique:tbhs_users',
            'password' => 'required',
            'id_situs' => 'required|array',
            'id_situs.*' => 'exists:tbhs_situs,id',
            'nama_staff' => 'required',
            'email' => 'required|unique:tbhs_users',
            'nomor_paspor' => 'required',
            'masa_aktif_paspor' => 'required',
            'nomor_visa' => 'required',
            'masa_aktif_visa' => 'required',
            'tanggal_join' => 'required',
            'posisi_kerja' => 'required',
            'id_jabatan' => 'required',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = new User;
        $user->id_admin = Str::lower($request->id_admin);
        $user->password = bcrypt($request->password);
        $user->id_situs = implode(',', $request->id_situs);
        $user->nama_staff = $request->nama_staff;
        $user->email = $request->email;
        $user->nomor_paspor = $request->nomor_paspor;
        $user->masa_aktif_paspor = $request->masa_aktif_paspor;
        $user->nomor_visa = $request->nomor_visa;
        $user->masa_aktif_visa = $request->masa_aktif_visa;
        $user->tanggal_join = $request->tanggal_join;
        $user->posisi_kerja = $request->posisi_kerja;
        $user->id_jabatan = $request->id_jabatan;
        $user->status = $request->status;

        $store = $user->save();

        $user_parent_id = User::select('id')->where('id_admin',$request->id_admin)->first();
        $nama_posisi = Posisi::select('nama_posisi')->where('id',$request->posisi_kerja)->first();
        $nama_jabatan = Jabatan::select('nama_jabatan')->where('id',$request->id_jabatan)->first();

        $array['user_parent_id'] = $user_parent_id->id;
        $array['id_admin'] = $request->id_admin;
        $array['posisi_kerja'] = $request->posisi_kerja;
        $array['nama_posisi'] = $nama_posisi->nama_posisi;
        $array['id_jabatan'] = $request->id_jabatan;
        $array['nama_jabatan'] = $nama_jabatan->nama_jabatan;

        Logposisijabatan::updateOrCreate([
            'user_parent_id' => $array['user_parent_id'],
            'id_admin' => $array['id_admin'],
            'posisi_kerja'  => $array['posisi_kerja'],
            'id_jabatan' => $array['id_jabatan'],
        ], [
            'user_parent_id' => $array['user_parent_id'],
            'id_admin' => $array['id_admin'],
            'posisi_kerja'  => $array['posisi_kerja'],
            'nama_posisi' =>  $array['nama_posisi'],
            'id_jabatan' => $array['id_jabatan'],
            'nama_jabatan' => $array['nama_jabatan']
        ]);

        return $store ? redirect()->route('daftaruser.index')->with('success', 'Data berhasil disimpan') : redirect()->back()->with('danger', 'Gagal simpan! Silakan hubungi Administrator');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_admin' => 'required|unique:tbhs_users,id_admin,' . $request->id . ',id',
            'id_situs' => 'required|array',
            'id_situs.*' => 'exists:tbhs_situs,id',
            'nama_staff' => 'required',
            'email' => 'required',
            'nomor_paspor' => 'required',
            'masa_aktif_paspor' => 'required',
            'nomor_visa' => 'required',
            'masa_aktif_visa' => 'required',
            'tanggal_join' => 'required',
            'posisi_kerja' => 'required',
            'id_jabatan' => 'required',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::where('id', $request->id)->firstOrFail();
        $user->id_admin = $request->id_admin;
        $user->id_situs = implode(',', $request->id_situs);
        $user->nama_staff = $request->nama_staff;
        $user->email = $request->email;
        $user->nomor_paspor = $request->nomor_paspor;
        $user->masa_aktif_paspor = $request->masa_aktif_paspor;
        $user->nomor_visa = $request->nomor_visa;
        $user->masa_aktif_visa = $request->masa_aktif_visa;
        $user->tanggal_join = $request->tanggal_join;
        $user->posisi_kerja = $request->posisi_kerja;
        $user->id_jabatan = $request->id_jabatan;
        $user->status = $request->status;

        $store = $user->save();

        $nama_posisi = Posisi::select('nama_posisi')->where('id',$request->posisi_kerja)->first();
        $nama_jabatan = Jabatan::select('nama_jabatan')->where('id',$request->id_jabatan)->first();

        $array['user_parent_id'] = $request->id;
        $array['id_admin'] = $request->id_admin;
        $array['posisi_kerja'] = $request->posisi_kerja;
        $array['nama_posisi'] = $nama_posisi->nama_posisi;
        $array['id_jabatan'] = $request->id_jabatan;
        $array['nama_jabatan'] = $nama_jabatan->nama_jabatan;
        $array['updated_at'] = new DateTime();

        Logposisijabatan::updateOrCreate([
            'user_parent_id' => $array['user_parent_id'],
            'id_admin' => $array['id_admin'],
            'posisi_kerja'  => $array['posisi_kerja'],
            'id_jabatan' => $array['id_jabatan'],
            'updated_at' => $array['updated_at']
        ], [
            'user_parent_id' => $array['user_parent_id'],
            'id_admin' => $array['id_admin'],
            'posisi_kerja'  => $array['posisi_kerja'],
            'nama_posisi' =>  $array['nama_posisi'],
            'id_jabatan' => $array['id_jabatan'],
            'nama_jabatan' => $array['nama_jabatan'],
            'updated_at' => $array['updated_at']
        ]);

        return $store ? redirect()->route('daftaruser.index')->with('success', 'Data edit berhasil disimpan')
            : redirect()->back()->with('danger', 'Gagal update! Silakan hubungi Administrator');
    }

    public function destroy(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $user = User::where('id', $request->id)->firstOrFail();
        $destroy = $user->delete();

        return $destroy ? response()->json(['success' => true, 'message' => 'Data berhasil dihapus']) : response()->json(['success' => false, 'message' => 'Gagal hapus! Silakan hubungi Administrator']);
    }

    public function updatePasswordForm(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        return view('pages.daftaruser.password');
    }

    public function updatePassword(Request $request)
    {
        # Validation
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|confirmed',
        ]);

        #Match The Old Password
        if(!Hash::check($request->old_password, auth()->user()->password)){
            return back()->with("error", "Password Lama Tidak Cocok!");
        }

        #Update the new Password
        User::whereId(auth()->user()->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        return back()->with("status", "Password Berhasil Diganti!");
    }

    public function resetPasswordForm(Request $request)
    {
        $akses = AuthLink::access_url(auth()->user()->id_admin, $request->segment(1));
        if ($akses[0]->nilai == 0) {
            return view('error');
        }

        $users = User::orderBy('id','ASC')->pluck('id_admin', 'id_admin');

        return view('pages.daftaruser.resetpwd')->with([
            'users' => $users,
        ]);
    }

    public function resetPassword(Request $request)
    {
        # Validation
        $request->validate([
            'new_password' => 'required|confirmed',
        ]);

        #Reset the new Password
        $user = User::where('id_admin', $request->userid)->firstOrFail();
        $user->password = $request->new_password;
        $reset = $user->save();

        return $reset ? redirect()->route('resetpassword.resetpwd')->with('success', 'Password berhasil direset') : redirect()->back()->with('danger', 'Gagal reset password!!! Silakan hubungi Administrator');
    }

    public function load($id = 0)
	{
        $privileges = Privilegeaccess::where('id_admin',$id)->get();
		return response()->json($privileges);
	}

    public function privileges(Request $request)
    {
        $delete = Privilegeaccess::where('id_admin', $request->id_admin)->delete();

        if (!empty($request->input('menu'))) {
            foreach ($request->input('menu') as $privileges)
            {
                $success = Privilegeaccess::updateOrCreate([
                    'id_admin' => $request->id_admin,
                    'menu_id' => trim($privileges, " ")
                ], [
                    'id_admin' => $request->id_admin,
                    'menu_id' => trim($privileges, " ")
                ]);
            }
            return redirect()->route('daftaruser.index')->with('success', 'Data assign menu berhasil disimpan');
        } else {
            return redirect()->back()->with('danger', 'Gagal simpan! Silakan hubungi Administrator');
        }
    }
}
