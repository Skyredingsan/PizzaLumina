<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/api/products', operationId: 'listProducts', description: 'Публичный каталог с постраничной навигацией.', summary: 'Получить список товаров', tags: ['Products'], parameters: [
    new OA\Parameter(
        name: 'page',
        description: 'Номер страницы.',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 1, minimum: 1),
        example: 1,
    ),
    new OA\Parameter(
        name: 'per_page',
        description: 'Количество товаров на странице (не более 100).',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 15, maximum: 100, minimum: 1),
        example: 15,
    ),
], responses: [
    new OA\Response(
        response: 200,
        description: 'Список товаров.',
        content: new OA\JsonContent(ref: '#/components/schemas/ProductListResponse'),
    ),
    new OA\Response(
        response: 422,
        description: 'Некорректные параметры пагинации.',
        content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
    ),
    ])]

#[OA\Post(path: '/api/cart/items', operationId: 'addCartItem', description: 'Создаёт корзину при необходимости или увеличивает количество существующей позиции.', summary: 'Добавить товар в корзину', security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(
    required: true,
    content: new OA\JsonContent(
        required: ['product_id'],
        properties: [
            new OA\Property(
                property: 'product_id',
                description: 'ID существующего товара.',
                type: 'integer',
                example: 1,
                minimum: 1,
            ),
            new OA\Property(
                property: 'quantity',
                description: 'Количество; по умолчанию 1.',
                type: 'integer',
                example: 2,
                default: 1,
                maximum: 99,
                minimum: 1,
            ),
        ],
        type: 'object',
        example: ['product_id' => 1, 'quantity' => 2],
    ),
), tags: ['Cart'], responses: [
    new OA\Response(
        response: 201,
        description: 'Товар добавлен, возвращена актуальная корзина.',
        content: new OA\JsonContent(ref: '#/components/schemas/CartResponse'),
    ),
    new OA\Response(
        response: 401,
        description: 'JWT отсутствует или недействителен.',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
    ),
    new OA\Response(
        response: 422,
        description: 'Ошибка валидации, товар не существует или превышен лимит корзины.',
        content: new OA\JsonContent(
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/ValidationError'),
                new OA\Schema(ref: '#/components/schemas/ErrorResponse'),
            ],
        ),
    ),
])]

#[OA\Post(path: '/api/orders', operationId: 'createOrder', description: 'Создаёт снимок позиций корзины и очищает корзину после успешного оформления.', summary: 'Создать заказ из корзины', security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(
    required: false,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(
                property: 'delivery_method',
                description: 'Способ доставки; по умолчанию courier.',
                type: 'string',
                example: 'courier',
                default: 'courier',
                enum: ['pickup', 'courier'],
            ),
            new OA\Property(
                property: 'address',
                description: 'Если объект передан, четыре основных поля обязательны.',
                required: ['region', 'city', 'street', 'building'],
                properties: [
                    new OA\Property(
                        property: 'region',
                        type: 'string',
                        example: 'Московская область',
                        maxLength: 100,
                        minLength: 2,
                    ),
                    new OA\Property(
                        property: 'city',
                        type: 'string',
                        example: 'Москва',
                        maxLength: 100,
                        minLength: 2,
                    ),
                    new OA\Property(
                        property: 'street',
                        type: 'string',
                        example: 'Тверская',
                        maxLength: 200,
                        minLength: 2,
                    ),
                    new OA\Property(
                        property: 'building',
                        type: 'string',
                        example: '1',
                        maxLength: 20,
                        minLength: 1,
                    ),
                    new OA\Property(
                        property: 'entrance',
                        type: 'string',
                        example: '2',
                        nullable: true,
                        maxLength: 20,
                    ),
                    new OA\Property(
                        property: 'apartment',
                        type: 'string',
                        example: '15',
                        nullable: true,
                        maxLength: 20,
                    ),
                    new OA\Property(
                        property: 'zip',
                        type: 'string',
                        example: '125009',
                        nullable: true,
                        maxLength: 20,
                    ),
                ],
                type: 'object',
            ),
        ],
        type: 'object',
        example: [
            'delivery_method' => 'courier',
            'address' => [
                'region' => 'Московская область',
                'city' => 'Москва',
                'street' => 'Тверская',
                'building' => '1',
                'entrance' => '2',
                'apartment' => '15',
                'zip' => '125009',
            ],
        ],
    ),
), tags: ['Orders'], responses: [
    new OA\Response(
        response: 201,
        description: 'Заказ создан.',
        content: new OA\JsonContent(ref: '#/components/schemas/OrderResponse'),
    ),
    new OA\Response(
        response: 401,
        description: 'JWT отсутствует или недействителен.',
        content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'),
    ),
    new OA\Response(
        response: 422,
        description: 'Ошибка валидации, пустая корзина или превышен лимит заказа.',
        content: new OA\JsonContent(
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/ValidationError'),
                new OA\Schema(ref: '#/components/schemas/ErrorResponse'),
            ],
        ),
    ),
])]
final class Endpoints
{
}
