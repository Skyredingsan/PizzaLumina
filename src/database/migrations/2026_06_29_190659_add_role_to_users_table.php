<?php

declare(strict_types=1);

use App\Modules\User\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)
                ->default(UserRole::Customer->value)
                ->after('email');
        });

        $allowed = implode("', '", array_column(UserRole::cases(), 'value'));
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('{$allowed}'))");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
