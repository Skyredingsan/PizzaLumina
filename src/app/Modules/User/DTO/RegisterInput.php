<?php

declare(strict_types=1);

namespace App\Modules\User\DTO;

final readonly class RegisterInput
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $email,
        public string $password,
    ) {
    }
}
