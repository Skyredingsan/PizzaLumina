<?php

declare(strict_types=1);

namespace Tests\Feature\OpenApi;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class OpenApiDocumentationTest extends TestCase
{
    public function test_documentation_is_generated_and_matches_application_routes(): void
    {
        $exitCode = Artisan::call(command: 'l5-swagger:generate');

        $this->assertSame(0, $exitCode, Artisan::output());

        $this->get(uri: '/api/doc')
            ->assertOk()
            ->assertSee(value: 'PizzaLumina API');

        $response = $this->getJson(uri: '/docs')->assertOk();

        /** @var array<string, mixed> $specification */
        $specification = $response->json();
        $this->assertSame('3.0.0', $specification['openapi'] ?? null);
        $this->assertSame('/', $specification['servers'][0]['url'] ?? null);

        /** @var array<string, array<string, mixed>> $documentedPaths */
        $documentedPaths = $specification['paths'] ?? [];
        $expectedOperations = [
            ['GET', '/api/products', 'get'],
            ['POST', '/api/cart/items', 'post'],
            ['POST', '/api/orders', 'post'],
        ];

        foreach ($expectedOperations as [$httpMethod, $path, $operation]) {
            $route = $this->app->make(abstract: 'router')
                ->getRoutes()
                ->match(request: Request::create(uri: $path, method: $httpMethod));

            $this->assertContains($httpMethod, $route->methods());
            $this->assertArrayHasKey($path, $documentedPaths);
            $this->assertArrayHasKey($operation, $documentedPaths[$path]);
        }

        /** @var array<string, mixed> $cartOperation */
        $cartOperation = $documentedPaths['/api/cart/items']['post'];
        /** @var array<string, mixed> $orderOperation */
        $orderOperation = $documentedPaths['/api/orders']['post'];

        $this->assertSame(
            [['bearerAuth' => []]],
            $cartOperation['security'] ?? null,
        );
        $this->assertSame(
            [['bearerAuth' => []]],
            $orderOperation['security'] ?? null,
        );
    }
}
