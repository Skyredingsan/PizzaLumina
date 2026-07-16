<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Modules\Order\Enums\DeliveryMethod;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property OrderStatus $status
 * @property int $total_amount
 * @property DeliveryMethod $delivery_method
 * @property string|null $address_region
 * @property string|null $address_city
 * @property string|null $address_street
 * @property string|null $address_building
 * @property string|null $address_entrance
 * @property string|null $address_apartment
 * @property string|null $address_zip
 * @property Carbon|null $paid_at
 * @property Carbon|null $cancelled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Order extends Model
{
    protected $table = 'orders';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'status',
        'total_amount',
        'delivery_method',
        'address_region',
        'address_city',
        'address_street',
        'address_building',
        'address_entrance',
        'address_apartment',
        'address_zip',
        'paid_at',
        'cancelled_at',
    ];

    /** @var array<string, class-string|string> */
    protected $casts = [
        'status' => OrderStatus::class,
        'delivery_method' => DeliveryMethod::class,
        'total_amount' => 'integer',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, covariant $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(related: User::class);
    }

    /**
     * @return HasMany<OrderItem, covariant $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(related: OrderItem::class);
    }
}
