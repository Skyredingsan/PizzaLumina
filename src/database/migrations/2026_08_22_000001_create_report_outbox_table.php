<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_outbox', function (Blueprint $table): void {
            $table->id();
            $table->ulid('report_id');
            $table->string('event', 64);
            $table->json('payload');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['report_id', 'event']);
            $table->index(['published_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_outbox');
    }
};
