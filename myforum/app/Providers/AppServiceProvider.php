<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use App\Helpers\AuthLink;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Inject variabel global khusus untuk layout utama
        View::composer('layouts.app', function ($view) {
            // Ambil pengumuman (HTML dibolehkan)
            $pengumumanHtml = '';
            try {
                $row = Setting::select('pengumuman')->first();
                $pengumumanHtml = $row->pengumuman ?? '';
            } catch (\Throwable $e) {
                $pengumumanHtml = '';
            }

            // Ambil allowed menu IDs untuk user aktif
            $allowed = [];
            try {
                if (Auth::check()) {
                    $allowed = AuthLink::allowedMenuIds() ?? [];
                }
            } catch (\Throwable $e) {
                $allowed = [];
            }

            $view->with(compact('pengumumanHtml', 'allowed'));
        });
    }
}
