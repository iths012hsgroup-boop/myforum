<?php

namespace App\Exports;

use App\Models\AbsensiReport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AbsensiReportExport implements FromCollection, WithHeadings
{
    protected ?string $tahun;
    protected ?string $periode;   // contoh '2025-1' atau null
    protected ?int $idSitus;

    public function __construct(?string $tahun = null, ?string $periode = null, ?int $idSitus = null)
    {
        $this->tahun   = $tahun;
        $this->periode = $periode;
        $this->idSitus = $idSitus;
    }

    public function collection()
    {
        return DB::table('tbhs_absensireport as r')
            ->leftJoin('tbhs_situs as s', 's.id', '=', 'r.id_situs')
            ->select(
                'r.id_admin',
                'r.nama_staff',
                's.nama_situs',
                'r.periode',
                'r.sakit',
                'r.izin',
                'r.telat',
                'r.tanpa_kabar',
                'r.cuti',
                'r.total_absensi'
            )
            ->when($this->idSitus, function ($q) {
                $q->where('r.id_situs', $this->idSitus);
            })
            // kalau ada periode spesifik → filter ketat
            ->when($this->periode, function ($q) {
                $q->where('r.periode', $this->periode);
            })
            // kalau periode null tapi tahun ada → semua periode tahun tsb
            ->when(!$this->periode && $this->tahun, function ($q) {
                $q->where('r.periode', 'like', $this->tahun.'-%');
            })
            ->orderBy('r.nama_staff')
            ->get();
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

