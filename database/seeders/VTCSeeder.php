<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VTCSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $consumers = [
            [
                'consumerial_type' => 'Internal',
                'consumerial_code' => 'INT-001',
                'consumerial_name' => 'Divisi Operasional',
                'consumerial_notes' => 'Konsumen internal untuk kegiatan operasional harian',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'consumerial_type' => 'Internal',
                'consumerial_code' => 'INT-002',
                'consumerial_name' => 'Divisi Marketing',
                'consumerial_notes' => 'Konsumen internal untuk kegiatan marketing dan promosi',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'consumerial_type' => 'External',
                'consumerial_code' => 'EXT-001',
                'consumerial_name' => 'PT Mitra Sejahtera',
                'consumerial_notes' => 'Klien external untuk project konstruksi',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'consumerial_type' => 'External',
                'consumerial_code' => 'EXT-002',
                'consumerial_name' => 'CV Karya Mandiri',
                'consumerial_notes' => 'Partner external untuk layanan transportasi',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'consumerial_type' => 'Internal',
                'consumerial_code' => 'INT-003',
                'consumerial_name' => 'Divisi Maintenance',
                'consumerial_notes' => 'Divisi pemeliharaan dan perbaikan',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('vehicle_and_tools_consumers')->insert($consumers);
    }
}
