<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Forumaudit;
use App\Models\SettingPeriode;
use Illuminate\Support\Facades\DB;

class UpdatePeriode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-periode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    // public function handle()
    // {
    //     //
    //     $rowsUpdated = DB::table('tbhs_forum')->get()->each(function ($item) {
    //         $year = date('Y', strtotime($item->created_at));
    //         $month = date('m', strtotime($item->created_at));
    //         $periodeValue = $year . ($month <= 6 ? '1' : '2');

    //         DB::table('tbhs_forum')
    //             ->where('id', $item->id)
    //             ->update(['periode' => $periodeValue]);
    //     });

    //     $this->info("Updated {$rowsUpdated->count()} rows.");
    // }
    public function handle()
    {
        // Get all forums
        $forums = Forumaudit::all();

        // Get all periode settings
        $periodes = SettingPeriode::all();

        $rowsUpdated = 0;

        foreach ($forums as $forum) {
            foreach ($periodes as $periode) {
                // Check if created_at is within the periode range
                if ($forum->created_at >= $periode->bulan_dari && $forum->created_at <= $periode->bulan_ke) {
                    // Update the forum periode based on the matching setting periode
                    $forum->update([
                        'periode' => $periode->tahun . $periode->periode
                    ]);
                    $rowsUpdated++;
                    break; // Stop checking once a matching periode is found
                }
            }
        }

        $this->info("Updated {$rowsUpdated} rows.");
    }
}
