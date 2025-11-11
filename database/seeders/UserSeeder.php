<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'username' => 'superadmin',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Super Admin',
                'role' => 'superadmin',
                'jabatan' => 'Super Administrator',
                'email' => 'superadmin@geodipa.com',
                'added_at' => now(),
            ],
            [
                'username' => 'admin',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Admin BBM',
                'role' => 'admin',
                'jabatan' => 'Administrator BBM',
                'email' => 'admin@geodipa.com',
                'added_at' => now(),
            ],
            [
                'username' => 'manager',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Manager Operasional',
                'role' => 'manager',
                'jabatan' => 'Manager',
                'email' => 'manager@geodipa.com',
                'added_at' => now(),
            ],
            [
                'username' => 'requester1',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'John Doe',
                'role' => 'requester',
                'jabatan' => 'Staff Operasional',
                'email' => 'john.doe@geodipa.com',
                'added_at' => now(),
            ],
            [
                'username' => 'requester2',
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Jane Smith',
                'role' => 'requester',
                'jabatan' => 'Staff Lapangan',
                'email' => 'jane.smith@geodipa.com',
                'added_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}