<?php

namespace App\Exports;

use App\Models\AbsensiReport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AbsensiReportExport implements FromCollection, WithHeadings
{
    protected $tahun;
    protected $periode;
    protected $idSitus;

    public function __construct($tahun, $periode = null, $idSitus = null)
    {
        $this->tahun   = $tahun;
        $this->periode = $periode;   // "2025-1" atau null
        $this->idSitus = $idSitus;   // int atau null
    }

    public function collection()
    {
        $query = DB::table('tbhs_absensireport as r')
            ->join('tbhs_users as u', 'u.id_admin', '=', 'r.id_admin')
            ->leftJoin('tbhs_situs as s', function ($join) {
                $join->on(DB::raw('FIND_IN_SET(s.id, u.id_situs)'), '>', DB::raw('0'));
            })
            ->when($this->idSitus, function ($q) {
                $q->where('r.id_situs', $this->idSitus);
            })
            ->when($this->periode, function ($q) {
                $q->where('r.periode', $this->periode);
            }, function ($q) {
                // kalau periode null, filter semua periode di tahun tsb
                $q->where('r.periode', 'like', $this->tahun.'-%');
            })
            ->groupBy(
                'r.id_admin',
                'r.nama_staff',
                'r.id_situs',
                'r.periode',
                'r.sakit',
                'r.izin',
                'r.telat',
                'r.tanpa_kabar',
                'r.cuti',
                'r.total_absensi'
            )
            ->selectRaw("
                r.id_admin,
                r.nama_staff,
                GROUP_CONCAT(DISTINCT s.nama_situs ORDER BY s.nama_situs SEPARATOR ', ') AS nama_situs,
                r.periode,
                r.sakit,
                r.izin,
                r.telat,
                r.tanpa_kabar,
                r.cuti,
                r.total_absensi
            ")
            ->orderBy('r.id_admin')
            ->get();

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID Admin',
            'Nama Staff',
            'Nama Situs',
            'Periode',
            'Sakit',
            'Izin',
            'Telat',
            'Tanpa Kabar',
            'Cuti',
            'Total Absensi',
        ];
    }
}


