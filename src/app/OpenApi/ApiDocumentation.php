<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0.0', description: 'HTTP API интернет-магазина PizzaLumina.', title: 'PizzaLumina API')]
#[OA\Server(
    url: '/',
    description: 'Текущий хост Swagger UI',
)]
#[OA\SecurityScheme(securityScheme: 'bearerAuth', type: 'http', description: 'JWT из POST /api/auth/login. Введите только токен.', bearerFormat: 'JWT', scheme: 'bearer')]
#[OA\Tag(name: 'Products', description: 'Каталог товаров')]
#[OA\Tag(name: 'Cart', description: 'Корзина авторизованного пользователя')]
#[OA\Tag(name: 'Orders', description: 'Заказы авторизованного пользователя')]
final class ApiDocumentation
{
}
