<?php

declare(strict_types=1);

namespace App\Modules\Cart\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Modules\User\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Modules\Cart\Models\CartItem> $items
 */
class Cart extends Model
{
    protected $fillable = ['user_id'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(related: User::class);
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(related: CartItem::class);
    }
}
