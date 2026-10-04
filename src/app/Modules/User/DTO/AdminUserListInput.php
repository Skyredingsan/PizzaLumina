<?php

declare(strict_types=1);

namespace App\Modules\User\DTO;

final readonly class AdminUserListInput
{
    public function __construct(public int $perPage)
    {
    }
}
