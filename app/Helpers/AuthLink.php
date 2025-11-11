<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthLink
{
    /**
     * (Legacy) Dipakai di banyak controller-mu.
     * Mengembalikan collection dengan field ->nilai (COUNT).
     */
    public static function access_url($id_admin, $menu_link)
    {
        $value = DB::table('tbhs_privilege_access')
            ->join('tbhs_menu', 'tbhs_menu.menu_id', '=', 'tbhs_privilege_access.menu_id')
            ->where('tbhs_privilege_access.id_admin', $id_admin)
            ->where('tbhs_menu.menu_link', $menu_link)
            ->select(DB::raw('COUNT(tbhs_privilege_access.id) AS nilai'))
            ->get();

        return $value;
    }

    /**
     * Cek cepat: apakah user punya privilege?
     * $menu bisa berupa menu_link (contoh: 'topik', 'dashinfo')
     * atau menu_id (contoh: 'HSF018', 'HSF017').
     */
    public static function can(string $menu, ?string $id_admin = null): bool
    {
        $id_admin = $id_admin ?? (Auth::user()->id_admin ?? null);
        if (!$id_admin) return false;

        // Jika format HSFxxx -> anggap sebagai menu_id
        if (preg_match('/^HSF\d+$/i', $menu)) {
            return DB::table('tbhs_privilege_access')
                ->where('id_admin', $id_admin)
                ->where('menu_id', strtoupper($menu))
                ->exists();
        }

        // Selain itu -> anggap sebagai menu_link
        $menu_id = DB::table('tbhs_menu')->where('menu_link', $menu)->value('menu_id');
        if (!$menu_id) return false;

        return DB::table('tbhs_privilege_access')
            ->where('id_admin', $id_admin)
            ->where('menu_id', $menu_id)
            ->exists();
    }

    /**
     * Guard di controller: abort(403) jika tidak punya akses.
     * Contoh: AuthLink::ensure('topik');
     */
    public static function ensure(string $menu, ?string $id_admin = null): void
    {
        if (!self::can($menu, $id_admin)) {
            abort(403, 'Anda tidak memiliki hak akses.');
        }
    }

    /**
     * Optional: ambil semua menu_id yang diizinkan untuk user saat ini.
     */
    public static function allowedMenuIds(?string $id_admin = null): array
    {
        $id_admin = $id_admin ?? (Auth::user()->id_admin ?? null);
        if (!$id_admin) return [];

        return DB::table('tbhs_privilege_access')
            ->where('id_admin', $id_admin)
            ->pluck('menu_id')
            ->toArray();
    }
}
