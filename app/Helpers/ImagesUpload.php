<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImagesUpload {

    /**
     * Upload dengan nama random
     *
     * @param  \Illuminate\Http\UploadedFile $data
     * @param  string $location  contoh: 'gambar_topik/situs-a/'
     * @param  string $disk      contoh: 'spaces' atau 'public'
     * @return string            path di bucket, contoh: 'uploads/gambar_topik/situs-a/xxxxx.jpg'
     */
    public static function upload($data, $location, $disk = 'spaces')
    {
        $fileName = Str::random(20).'.'.$data->getClientOriginalExtension();

        // rapikan location biar gak dobel slash
        $location = trim($location, '/').'/';

        // path yang akan disimpan di Spaces dan di DB
        $path = '/'.$location.$fileName;

        // SIMPAN ke DO Spaces (disk 'spaces')
        Storage::disk($disk)->put($path, file_get_contents($data), 'public');

        // return PATH, bukan URL
        return $path;
    }

    /**
     * Upload pakai nama asli file
     */
    public static function uploadOriginal($data, $location, $disk = 'spaces')
    {
        $location = trim($location, '/').'/';

        $fileName = str_replace(':','-',$data->getClientOriginalName());
        $path = '/'.$location.$fileName;

        Storage::disk($disk)->put($path, file_get_contents($data), 'public');

        return $path;
    }

}
