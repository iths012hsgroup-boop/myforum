<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Privilegeaccess;
use App\Models\Menu;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // 'id_admin','password','id_situs','nama_staff','email','nomor_paspor','masa_aktif_paspor','tanggal_join','posisi_kerja','status'

        User::firstOrCreate([
            "id_admin" => 'admin711'
        ],[
            "id_admin" => 'admin711',
            "password" => bcrypt('qwe778800'),
            "id_situs" => null,
            "nama_staff" => 'Admin711',
            "email" => 'admin711.hsgroup@gmail.com',
            "nomor_paspor" => null,
            "masa_aktif_paspor" => null,
            "tanggal_join" => null,
            "posisi_kerja" => null,
            "status" => 1,
            "remember_token" => null
        ]);

        User::firstOrCreate([
            "id_admin" => 'spv_unlimited'
        ],[
            "id_admin" => 'spv_unlimited',
            "password" => bcrypt('abc668800'),
            "id_situs" => null,
            "nama_staff" => 'SPV Unlimited',
            "email" => 'spv.hsgroup@gmail.com',
            "nomor_paspor" => null,
            "masa_aktif_paspor" => null,
            "tanggal_join" => null,
            "posisi_kerja" => null,
            "status" => 1,
            "remember_token" => null
        ]);

        $menu = [
            [
                'menu_id' => 'HSF001',
                'menu_deskripsi' => 'HS Forum',
            ],
            [
                'menu_id' => 'HSF002',
                'menu_deskripsi' => 'HS Forum (Audit)',
            ],
            [
                'menu_id' => 'HSF003',
                'menu_deskripsi' => 'Daftar Situs',
            ],
            [
                'menu_id' => 'HSF004',
                'menu_deskripsi' => 'Daftar Jabatan',
            ],
            [
                'menu_id' => 'HSF005',
                'menu_deskripsi' => 'Daftar Staff',
            ],
            [
                'menu_id' => 'HSF006',
                'menu_deskripsi' => 'Setting Pengumuman',
            ],
            [
                'menu_id' => 'HSF007',
                'menu_deskripsi' => 'Ubah Password',
            ],
            [
                'menu_id' => 'HSF008',
                'menu_deskripsi' => 'Akses Status Kesalahan dan Status Topik',
            ],
        ];

        Menu::Create($menu);

        $privilegeaccess = [
            [
                'id_admin'  => 'admin711',
                'menu_id'   => 'HSF001',
            ],
            [
                'id_admin'  => 'admin711',
                'menu_id'   => 'HSF002',
            ],
            [
                'id_admin'  => 'admin711',
                'menu_id'   => 'HSF003',
            ],
            [
                'id_admin'  => 'admin711',
                'menu_id'   => 'HSF004',
            ],
            [
                'id_admin'  => 'admin711',
                'menu_id'   => 'HSF005',
            ],
            [
                'id_admin'  => 'admin711',
                'menu_id'   => 'HSF006',
            ],
            [
                'id_admin'  => 'admin711',
                'menu_id'   => 'HSF007',
            ],
            [
                'id_admin'  => 'admin711',
                'menu_id'   => 'HSF008',
            ],
        ];

        Privilegeaccess::Create($privilegeaccess);
    }
}
