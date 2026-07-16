<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // updateOrCreate akan membuat baru jika belum ada, atau update jika sudah ada
        User::updateOrCreate(
            ['email' => 'admin@larashop.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123456'),
                'role' => 'admin',
                'phone' => '081234567890',
            ]
        );
    }
}