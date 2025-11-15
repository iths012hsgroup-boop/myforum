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
    $dataforumaudit = Forumaudit::where('slug', $slug)->firstOrFail();

    $dataforumauditpost = Forumauditpost::where('slug', $dataforumaudit->slug)
        ->where('parent_forum_id', $dataforumaudit->id)
        ->where('parent_case_id', $dataforumaudit->case_id)
        ->orderBy('created_at', 'asc')
        ->get();

    $canUpdateStatus = Privilegeaccess::where('id_admin', auth()->user()->id_admin)
        ->where('menu_id', 'HSF008')
        ->exists();

    $periodes = SettingPeriode::orderBy('tahun')->orderBy('periode')->get();

    $periodeOptions = $periodes->map(function ($p) use ($dataforumaudit) {
        $value = $p->tahun . $p->periode;
        return (object) [
            'value'    => $value,
            'label'    => $p->bulan_dari . ' - ' . $p->bulan_ke,
            'selected' => $dataforumaudit->periode === $value,
        ];
    });

    $imgUrl = $dataforumaudit->link_gambar ? Storage::url($dataforumaudit->link_gambar) : '';

    return view('pages.formauditor.comments', [
        'dataforumaudit'     => $dataforumaudit,
        'dataforumauditpost' => $dataforumauditpost,
        'canUpdateStatus'    => $canUpdateStatus,
        'imgUrl'             => $imgUrl,
        'periodeOptions'     => $periodeOptions,
    ]);
}
}
