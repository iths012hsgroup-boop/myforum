<?php

namespace App\Helpers;

use Storage;
use Crud;
use File;
use Illuminate\Support\Str;

class ImagesUpload {

    public static function upload($data, $location)
    {
        $fileName = Str::random(20).'.'.$data->getClientOriginalExtension();
        $path = 'uploads/'.$location.$fileName;
        $process = Storage::disk('public')->put($path, file_get_contents($data),'public');

        return $path;
    }

    public static function uploadOriginal($data, $location)
    {
        $fileName = $data->getClientOriginalName();
        $path = 'uploads/'.$location.str_replace(':','-',$fileName);
        if(Storage::disk('public')->exists($path)) {
            $process = Storage::disk('public')->put($path, file_get_contents($data),'public');

        } else {

            $process = Storage::disk('public')->put($path, file_get_contents($data),'public');
        }

        return $path;
    }

}
