<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property OrderStatus $status
 * @property string $total_amount
 * @property string $currency
 * @property string $address_region
 * @property string $address_city
 * @property string $address_street
 * @property string $address_building
 * @property string|null $address_entrance
 * @property string|null $address_apartment
 * @property string|null $address_zip
 * @property Carbon|null $paid_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Order extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'status',
        'total_amount',
        'currency',
        'address_region',
        'address_city',
        'address_street',
        'address_building',
        'address_entrance',
        'address_apartment',
        'address_zip',
        'paid_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status'       => OrderStatus::class,
            'total_amount' => 'decimal:2',
            'paid_at'      => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(related: User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(related: OrderItem::class);
    }
}
