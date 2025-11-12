<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AbsensiReportExport implements FromCollection, WithHeadings
{
    protected $tahun;
    protected $periode;
    protected $idSitusList; // <- array|null

    public function __construct($tahun, $periode = null, $idSitusList = null)
    {
        $this->tahun       = $tahun;
        $this->periode     = $periode;     // "2025-1" atau null
        $this->idSitusList = $idSitusList; // ['4','34'] atau null
    }

    public function collection()
    {
        $query = DB::table('tbhs_absensireport as r')
            ->leftJoin('tbhs_situs as s', function ($join) {
                // r.id_situs bisa "4,34"
                $join->on(DB::raw('FIND_IN_SET(s.id, r.id_situs)'), '>', DB::raw('0'));
            })
            ->when($this->idSitusList, function ($q) {
                $ids = $this->idSitusList;

                $q->where(function ($sub) use ($ids) {
                    foreach ($ids as $id) {
                        // r.id_situs LIKE "4,34" → cocokan per-id
                        $sub->orWhereRaw('FIND_IN_SET(?, r.id_situs)', [$id]);
                    }
                });
            })
            ->when($this->periode, function ($q) {
                $q->where('r.periode', $this->periode);
            }, function ($q) {
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
            ->orderByDesc('r.periode')
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

