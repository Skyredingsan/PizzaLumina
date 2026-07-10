<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('created')->index();

            $table->decimal('total_amount', 10, 2)->unsigned()->default(0);
            $table->char('currency', 3)->default('RUB');

            $table->string('address_region');
            $table->string('address_city');
            $table->string('address_street');
            $table->string('address_building');
            $table->string('address_entrance')->nullable();
            $table->string('address_apartment')->nullable();
            $table->string('address_zip', 20)->nullable();

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
