<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Forumaudit;
use App\Models\Forumauditpost;
use App\Models\Privilegeaccess;
use App\Models\SettingPeriode;

class HsForumController extends Controller
{
    /**
     * Halaman komentar forum (detail + daftar komentar + form update)
     */
    public function comments(string $slug)
    {
        // Data utama forum
        $dataforumaudit = Forumaudit::where('slug', $slug)->firstOrFail();

        // Daftar komentar
        $dataforumauditpost = Forumauditpost::where('forum_id', $dataforumaudit->id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Akses update status
        $canUpdateStatus = Privilegeaccess::where('id_admin', auth()->user()->id_admin)
            ->where('menu_id', 'HSF008')
            ->exists();

        // Periode (dropdown)
        $periodes = SettingPeriode::orderBy('tahun')->orderBy('periode')->get();

        // Build opsi periode + selected agar Blade bersih dari logika
        $periodeOptions = $periodes->map(function ($p) use ($dataforumaudit) {
            $value = $p->tahun . $p->periode; // contoh: 20241
            return (object) [
                'value'    => $value,
                'label'    => $p->bulan_dari . ' - ' . $p->bulan_ke,
                'selected' => $dataforumaudit->periode === $value,
            ];
        });

        // URL gambar (jika ada)
        $imgUrl = $dataforumaudit->link_gambar ? Storage::url($dataforumaudit->link_gambar) : '';

        return view('pages.formauditor.comments', [
            'dataforumaudit'       => $dataforumaudit,
            'dataforumauditpost'   => $dataforumauditpost,
            'canUpdateStatus'      => $canUpdateStatus,
            'imgUrl'               => $imgUrl,
            'periodeOptions'       => $periodeOptions,
        ]);
    }
}
