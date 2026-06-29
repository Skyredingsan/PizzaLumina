<?php

declare(strict_types=1);

namespace App\Modules\User\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role'  => $this->role->value,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
