<?php

declare(strict_types=1);

use App\Modules\Report\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('status', 16)->default(ReportStatus::Pending->value);
            $table->jsonb('parameters')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('created_by');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
