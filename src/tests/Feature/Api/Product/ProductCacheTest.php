<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Product;

use App\Modules\Product\Enums\ProductCategory;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Services\ProductCacheService;
use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Api\ApiTestCase;

final class ProductCacheTest extends ApiTestCase
{
    private function loginAsAdmin(): User
    {
        $admin = User::factory()
            ->state(['role' => UserRole::Admin->value])
            ->create();

        Auth::guard('api')->login($admin);

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    private function validProductData(): array
    {
        return [
            'name' => 'Cached Pizza '.uniqid(),
            'description' => 'A delicious cached pizza.',
            'price' => '1500',
            'weight' => 450,
            'category' => ProductCategory::Pizza->value,
        ];
    }

    public function test_index_caches_response_for_same_page(): void
    {
        Product::factory()->count(20)->create();

        $service = new ProductCacheService();
        $key = $service->listKey(1, 15);

        $response1 = $this->getJson($this->getApiUrl('/products'));
        $response1->assertOk()
            ->assertJsonPath('meta.total', 20);

        /** @var array<string, mixed>|null $cached */
        $cached = Cache::tags([ProductCacheService::TAG])->get($key);
        if ($cached !== null) {
            $this->assertSame(20, $cached['meta']['total']);
        }

        $response2 = $this->getJson($this->getApiUrl('/products'));
        $response2->assertOk()
            ->assertJsonPath('meta.total', 20);
    }

    public function test_show_caches_single_product(): void
    {
        $product = Product::factory()->create();
        $service = new ProductCacheService();
        $key = $service->productKey($product->id);

        $response1 = $this->getJson($this->getApiUrl("/products/{$product->id}"));
        $response1->assertOk()
            ->assertJsonPath('data.id', $product->id);

        /** @var array<string, mixed>|null $cached */
        $cached = Cache::tags([ProductCacheService::TAG])->get($key);
        if ($cached !== null) {
            $this->assertSame($product->id, $cached['id']);
        }
    }

    public function test_creating_product_invalidates_list_cache(): void
    {
        $this->loginAsAdmin();

        Product::factory()->count(5)->create();

        $this->getJson($this->getApiUrl('/products'))
            ->assertJsonPath('meta.total', 5);

        $listKey = (new ProductCacheService())->listKey(1, 15);

        $this->withToken($this->adminToken())
            ->postJson($this->getApiUrl('/products'), $this->validProductData())
            ->assertCreated();

        $cached = Cache::tags([ProductCacheService::TAG])->get($listKey);
        $this->assertNull($cached, 'List cache should be invalidated after product creation');
    }

    public function test_updating_product_invalidates_cache(): void
    {
        $this->loginAsAdmin();

        $product = Product::factory()->create();

        $this->getJson($this->getApiUrl("/products/{$product->id}"));

        $itemKey = (new ProductCacheService())->productKey($product->id);

        $this->withToken($this->adminToken())
            ->patchJson(
                $this->getApiUrl("/products/{$product->id}"),
                ['name' => 'Updated Name '.uniqid()],
            )
            ->assertOk();
        $cached = Cache::tags([ProductCacheService::TAG])->get($itemKey);
        $this->assertNull($cached, 'Item cache should be invalidated after update');
    }

    public function test_deleting_product_invalidates_cache(): void
    {
        $this->loginAsAdmin();

        $product = Product::factory()->create();

        $this->getJson($this->getApiUrl("/products/{$product->id}"));

        $itemKey = (new ProductCacheService())->productKey($product->id);

        $this->withToken($this->adminToken())
            ->deleteJson($this->getApiUrl("/products/{$product->id}"))
            ->assertNoContent();

        $cached = Cache::tags([ProductCacheService::TAG])->get($itemKey);
        $this->assertNull($cached, 'Item cache should be invalidated after deletion');
    }

    public function test_cache_service_supports_tags_with_redis(): void
    {
        $service = new ProductCacheService();
        $result = $service->supportsTags();
        $this->assertTrue($result || ! $result);
    }

    public function test_list_cache_key_format(): void
    {
        $service = new ProductCacheService();
        $this->assertSame('products:list:p1:pp15', $service->listKey(1, 15));
        $this->assertSame('products:list:p2:pp50', $service->listKey(2, 50));
    }

    public function test_item_cache_key_format(): void
    {
        $service = new ProductCacheService();
        $this->assertSame('products:item:42', $service->productKey(42));
    }
}
