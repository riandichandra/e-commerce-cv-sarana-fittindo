<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WilayahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Import data provinsi
    $provinces = [
        ['id' => 11, 'name' => 'Aceh'],
        ['id' => 12, 'name' => 'Sumatera Utara'],
        ['id' => 13, 'name' => 'Sumatera Barat'],
        ['id' => 14, 'name' => 'Riau'],
        ['id' => 15, 'name' => 'Jambi'],
        ['id' => 16, 'name' => 'Sumatera Selatan'],
        ['id' => 17, 'name' => 'Bengkulu'],
        ['id' => 18, 'name' => 'Lampung'],
        // ... dan seterusnya
    ];

    foreach ($provinces as $province) {
        DB::table('provinces')->insert($province);
    }

    // Import data kabupaten/kota, kecamatan, kelurahan
    // ...
    }
}