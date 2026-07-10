<?php

declare(strict_types=1);

namespace App\Modules\Order\Exceptions;

use RuntimeException;

final class EmptyCartException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(message: 'Невозможно оформить заказ: корзина пуста.');
    }
}
