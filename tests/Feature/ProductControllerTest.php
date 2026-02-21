<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Enums\RoleEnum;
use App\Models\Product;
use App\Models\User;

describe('GET /api/products', function () {

    it('is publicly accessible and returns all products as a data array', function () {
        /** @var \Tests\TestCase $this */
        Product::factory(3)->create();
        Product::factory(2)->inactive()->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'price', 'status', 'created_at', 'updated_at'],
                ],
            ]);
    });

});

describe('GET /api/products/{id}', function () {

    it('returns a single product wrapped in data with correct fields', function () {
        /** @var \Tests\TestCase $this */
        $product = Product::factory()->create([
            'name'  => 'Test Widget',
            'price' => 49.99,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'description', 'price', 'status', 'created_at', 'updated_at']])
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Test Widget');
    });

    it('returns 404 for a non-existent product', function () {
        /** @var \Tests\TestCase $this */
        $response = $this->getJson('/api/products/99999');

        $response->assertStatus(404);
    });

});

describe('POST /api/products', function () {

    it('creates a product as admin and returns 201 with product in data', function () {
        /** @var \Tests\TestCase $this */
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/products', [
                'name'        => 'New Gadget',
                'description' => 'A great gadget',
                'price'       => 199.99,
                'status'      => 'active',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Gadget')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('products', ['name' => 'New Gadget']);
    });

    it('returns 422 for missing name, negative price, and invalid status', function () {
        /** @var \Tests\TestCase $this */
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/products', [
                'name'   => '',
                'price'  => -10,
                'status' => 'discontinued',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'status']);
    });

    it('returns 403 when authenticated as a non-admin user', function () {
        /** @var \Tests\TestCase $this */
        $user = User::factory()->create(['role' => RoleEnum::User]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products', [
                'name'   => 'Sneaky Product',
                'price'  => 10.00,
                'status' => 'active',
            ]);

        $response->assertStatus(403);
    });

    it('returns 401 when unauthenticated', function () {
        /** @var \Tests\TestCase $this */
        $response = $this->postJson('/api/products', [
            'name'   => 'Ghost Product',
            'price'  => 10.00,
            'status' => 'active',
        ]);

        $response->assertStatus(401);
    });

});

describe('PUT /api/products/{id}', function () {

    it('partially updates a product as admin and returns 200', function () {
        /** @var \Tests\TestCase $this */
        $admin   = User::factory()->create(['role' => RoleEnum::Admin]);
        $product = Product::factory()->create(['price' => 50.00, 'status' => 'active']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/products/{$product->id}", [
                'price' => 75.00,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.price', '75.00');

        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'price' => 75.00,
        ]);
    });

});

describe('DELETE /api/products/{id}', function () {

    it('deletes a product as admin, returns 200, and removes it from the database', function () {
        /** @var \Tests\TestCase $this */
        $admin   = User::factory()->create(['role' => RoleEnum::Admin]);
        $product = Product::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Product deleted successfully.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    });

    it('returns 403 when authenticated as a non-admin user', function () {
        /** @var \Tests\TestCase $this */
        $user    = User::factory()->create(['role' => RoleEnum::User]);
        $product = Product::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(403);
    });

});
