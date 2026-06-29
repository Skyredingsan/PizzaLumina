<?php

namespace Database\Seeders;

use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        User::factory()->create([
            'name'  => 'Test Customer',
            'email' => 'customer@pizzalumina.test',
            'role'  => UserRole::Customer,
        ]);
    }
}
