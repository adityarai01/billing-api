<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['mobile_no' => '9999999999'],
            [
                'name'      => 'Super Admin',
                'email'     => 'admin@billmaster.in',
                'mobile_no' => '9999999999',
                'password'  => Hash::make('Admin@123'),
                'status'    => 1,
                'deleted'   => 0,
            ]
        );
    }
}
