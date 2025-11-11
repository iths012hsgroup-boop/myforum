<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    private function get_client_ip()
    {
        foreach (array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ) as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE || FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
    }

    public function loginPage()
    {
        date_default_timezone_set('Asia/Jakarta');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.hokiselalu88.com/akses/show_ip/'.$this->get_client_ip());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER,array("X-Requested-With : XMLHttpRequest"));
        $content = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($content, true);

        if($response){
            if($response['ip']==1){
                if($response['status']==true){
                        return view('pages.login');
                } else {
                    echo $this->errors();
                }
            } else {
                return view('pages.login');
            }
        } else {
            return view('pages.login');
        }
    }

    public function login(Request $request)
    {
        if(Auth::attempt(['id_admin' => $request->username, 'password' => $request->password])) {
            return redirect()->route('dashboard.index');
        } else {
            return redirect()->back()->with('danger', 'Username / Password Anda Masukan Salah!!!');
        }
    }

    public function logout()
    {
        Session::flush();
        Auth::logout();
        return redirect()->route('login');
    }

    public function errors()
    {
        return view('error');
    }
}
