<?php

namespace Database\Seeders;

use App\Models\Sekolah;
use Illuminate\Database\Seeder;

class SekolahSeeder extends Seeder
{
 public function run(): void
 {
 // [npsn, nama, jenjang, status, alamat, kecamatan, telepon, latitude, longitude]
 $data = [
 ['30304086', 'SD Negeri Tanjung Pagar 4', 'SD', 'Negeri',
 'Jl. Kelayan Besar I RT.03 No. 32', 'Banjarmasin Selatan',
 null, -3.3447500, 114.5993450],
 ['30304199', 'SMP Negeri 3 Banjarmasin', 'SMP', 'Negeri',
 'Jl. P. Antasari No. 107', 'Banjarmasin Timur',
 '0511-3253813', -3.3280000, 114.6040000],
 ['30315579', 'MAN 2 Kota Banjarmasin', 'SMA', 'Negeri',
 'Jl. Pramuka Km. 6 RT.20 No. 28, Komplek Semanda', 'Banjarmasin Timur',
 '0511-3258164', -3.3313361, 114.6307093],
 ['30304274', 'SMAS Muhammadiyah 1 Banjarmasin', 'SMA', 'Swasta',
 'Jl. S. Parman No. 221', 'Banjarmasin Barat',
 '0511-3364795', -3.3061000, 114.5878000],
 ['30304265', 'SMK Muhammadiyah 1 Banjarmasin', 'SMK', 'Swasta',
 'Jl. S. Parman No. 221', 'Banjarmasin Barat',
 '0511-6710009', -3.3061000, 114.5878000],
 ];

 foreach ($data as [$npsn, $nama, $jenjang, $status, $alamat, $kecamatan, $telepon, $lat, $lng]) {
 Sekolah::updateOrCreate(
 ['npsn' => $npsn],
 [
 'nama' => $nama,
 'jenjang' => $jenjang,
 'status' => $status,
 'alamat' => $alamat,
 'kecamatan' => $kecamatan,
 'telepon' => $telepon,
 'latitude' => $lat,
 'longitude' => $lng,
 ]
 );
 }
 }
}