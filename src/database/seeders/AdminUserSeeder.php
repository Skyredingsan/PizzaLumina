<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@pizzalumina.test'],
            [
                'name' => 'Admin',
                'phone' => '+70000000000',
                'password' => Hash::make((string) env('ADMIN_PASSWORD', 'Admin@12345')),
                'role' => UserRole::Admin,
            ],
        );
    }
}
