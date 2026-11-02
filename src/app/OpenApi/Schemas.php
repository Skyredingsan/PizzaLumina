<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Money',
    required: ['amount', 'rubles', 'currency'],
    properties: [
        new OA\Property(
            property: 'amount',
            description: 'Сумма в минимальных единицах валюты (копейках).',
            type: 'integer',
            example: 50000,
        ),
        new OA\Property(property: 'rubles', type: 'number', format: 'float', example: 500),
        new OA\Property(property: 'currency', type: 'string', example: 'RUB'),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'Product',
    required: ['id', 'name', 'description', 'price', 'weight', 'category', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Маргарита'),
        new OA\Property(
            property: 'description',
            type: 'string',
            example: 'Томаты, моцарелла и базилик',
            nullable: true,
        ),
        new OA\Property(property: 'price', ref: '#/components/schemas/Money'),
        new OA\Property(property: 'weight', type: 'number', format: 'float', example: 450),
        new OA\Property(property: 'category', type: 'string', example: 'pizza', enum: ['pizza', 'drink']),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2026-11-02T10:00:00+00:00',
            nullable: true,
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
            example: '2026-11-02T10:00:00+00:00',
            nullable: true,
        ),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'ProductListResponse',
    required: ['data', 'links', 'meta'],
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Product'),
        ),
        new OA\Property(
            property: 'links',
            required: ['first', 'last', 'prev', 'next'],
            properties: [
                new OA\Property(property: 'first', type: 'string', nullable: true),
                new OA\Property(property: 'last', type: 'string', nullable: true),
                new OA\Property(property: 'prev', type: 'string', nullable: true),
                new OA\Property(property: 'next', type: 'string', nullable: true),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'meta',
            required: ['current_page', 'last_page', 'per_page', 'total', 'from', 'to'],
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                new OA\Property(property: 'last_page', type: 'integer', example: 1),
                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                new OA\Property(property: 'total', type: 'integer', example: 1),
                new OA\Property(property: 'from', type: 'integer', example: 1, nullable: true),
                new OA\Property(property: 'to', type: 'integer', example: 1, nullable: true),
            ],
            type: 'object',
        ),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'CartProduct',
    required: ['id', 'name', 'price', 'category'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Маргарита'),
        new OA\Property(property: 'price', ref: '#/components/schemas/Money'),
        new OA\Property(property: 'category', type: 'string', example: 'pizza', enum: ['pizza', 'drink']),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'CartItem',
    required: ['id', 'product_id', 'quantity', 'product'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'product_id', type: 'integer', example: 1),
        new OA\Property(property: 'quantity', type: 'integer', example: 2, minimum: 1),
        new OA\Property(
            property: 'product',
            ref: '#/components/schemas/CartProduct',
            nullable: true,
        ),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'Cart',
    required: ['id', 'user_id', 'items', 'items_count', 'total_quantity', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 3),
        new OA\Property(property: 'user_id', type: 'integer', example: 7),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CartItem'),
        ),
        new OA\Property(property: 'items_count', type: 'integer', example: 1),
        new OA\Property(property: 'total_quantity', type: 'integer', example: 2),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2026-11-02T10:00:00+00:00',
            nullable: true,
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
            example: '2026-11-02T10:00:00+00:00',
            nullable: true,
        ),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'CartResponse',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Cart'),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'Address',
    required: ['region', 'city', 'street', 'building', 'entrance', 'apartment', 'zip'],
    properties: [
        new OA\Property(property: 'region', type: 'string', example: 'Московская область', nullable: true),
        new OA\Property(property: 'city', type: 'string', example: 'Москва', nullable: true),
        new OA\Property(property: 'street', type: 'string', example: 'Тверская', nullable: true),
        new OA\Property(property: 'building', type: 'string', example: '1', nullable: true),
        new OA\Property(property: 'entrance', type: 'string', example: '2', nullable: true),
        new OA\Property(property: 'apartment', type: 'string', example: '15', nullable: true),
        new OA\Property(property: 'zip', type: 'string', example: '125009', nullable: true),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'OrderItem',
    required: [
        'id',
        'order_id',
        'product_id',
        'product_name',
        'product_category',
        'product_price',
        'quantity',
        'line_total',
        'created_at',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 12),
        new OA\Property(property: 'order_id', type: 'integer', example: 5),
        new OA\Property(property: 'product_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'product_name', type: 'string', example: 'Маргарита'),
        new OA\Property(property: 'product_category', type: 'string', example: 'pizza'),
        new OA\Property(property: 'product_price', type: 'number', format: 'float', example: 500),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'line_total', type: 'number', format: 'float', example: 1000),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2026-11-02T10:00:00+00:00',
            nullable: true,
        ),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'Order',
    required: [
        'id',
        'user_id',
        'status',
        'total_amount',
        'delivery_method',
        'address',
        'paid_at',
        'cancelled_at',
        'created_at',
        'updated_at',
        'items',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 5),
        new OA\Property(property: 'user_id', type: 'integer', example: 7),
        new OA\Property(property: 'status', type: 'string', example: 'created'),
        new OA\Property(
            property: 'total_amount',
            description: 'Итоговая сумма заказа в рублях.',
            type: 'integer',
            example: 1000,
        ),
        new OA\Property(
            property: 'delivery_method',
            type: 'string',
            example: 'courier',
            enum: ['pickup', 'courier'],
        ),
        new OA\Property(property: 'address', ref: '#/components/schemas/Address'),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'cancelled_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/OrderItem'),
        ),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'OrderResponse',
    required: ['data'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Order'),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'ErrorResponse',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
        new OA\Property(property: 'error', type: 'string', example: 'empty_cart', nullable: true),
        new OA\Property(property: 'type', type: 'string', example: 'domain_error', nullable: true),
    ],
    type: 'object',
)]

#[OA\Schema(
    schema: 'ValidationError',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'The product id field is required.',
        ),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['product_id' => ['The product id field is required.']],
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
        ),
    ],
    type: 'object',
)]

final class Schemas
{
}
