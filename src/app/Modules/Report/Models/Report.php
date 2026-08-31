<?php

declare(strict_types=1);

namespace App\Modules\Report\Models;

use App\Modules\Report\Enums\ReportStatus;
use App\Modules\Report\Enums\ReportType;
use App\Modules\User\Models\User;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * @property string $id ULID primary key
 * @property ReportType $type Report type
 * @property ReportStatus $status Status enum value
 * @property array<string, mixed>|null $parameters Generation parameters (date range, filters)
 * @property string|null $file_path Path to file in MinIO disk
 * @property string|null $file_name Original file name
 * @property int|null $file_size File size in bytes
 * @property string|null $mime_type MIME type
 * @property string|null $error Error message if status=failed
 * @property string $created_by FK to users.id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 */
/** @use HasFactory<ReportFactory> */
class Report extends Model
{
    use HasFactory;
    use HasUlids;

    public const DISK = 'minio';

    protected $table = 'reports';

    protected $fillable = [
        'type',
        'status',
        'parameters',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'error',
        'created_by',
    ];

    protected $hidden = [
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'status' => ReportStatus::class,
            'type' => ReportType::class,
            'file_size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(related: User::class, foreignKey: 'created_by');
    }

    /**
     * Get a temporary download URL for the report file (valid for given minutes).
     */
    public function temporaryDownloadUrl(int $expiresMinutes = 5): string
    {
        if (!$this->file_path) {
            throw new RuntimeException(message: 'Report has no file path');
        }

        return Storage::disk(self::DISK)->temporaryUrl(
            $this->file_path,
            now()->addMinutes($expiresMinutes)
        );
    }

    /**
     * Check if the report file exists in MinIO.
     */
    public function fileExists(): bool
    {
        if (!$this->file_path) {
            return false;
        }

        return Storage::disk(self::DISK)->exists($this->file_path);
    }

    protected static function newFactory(): Factory
    {
        return ReportFactory::new();
    }
}
