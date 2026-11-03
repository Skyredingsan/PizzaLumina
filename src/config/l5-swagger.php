<?php

declare(strict_types=1);

return [
    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'PizzaLumina API',
            ],

            'routes' => [
                'api' => 'api/doc',
            ],

            'paths' => [
                // Relative URLs keep Swagger UI on the current host and protocol.
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', false),
                'swagger_ui_assets_path' => env(
                    'L5_SWAGGER_UI_ASSETS_PATH',
                    'vendor/swagger-api/swagger-ui/dist/',
                ),
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
                'annotations' => [
                    base_path('app/OpenApi'),
                ],
            ],
        ],
    ],
];
