<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Product;

describe('POST /api/orders', function () {

    it('creates an order successfully with multiple items', function () {
        /** @var \Tests\TestCase $this */
        $productA = Product::factory()->create(['price' => 100.00]);
        $productB = Product::factory()->create(['price' => 50.50]);

        $payload = [
            'customer_name'  => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'items' => [
                ['product_id' => $productA->id, 'qty' => 2],
                ['product_id' => $productB->id, 'qty' => 3],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'customer_name',
                    'customer_email',
                    'status',
                    'total_price',
                    'items',
                ],
            ])
            ->assertJsonPath('data.customer_name', 'Jane Doe')
            ->assertJsonPath('data.customer_email', 'jane@example.com')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('orders', [
            'customer_name'  => 'Jane Doe',
            'customer_email' => 'jane@example.com',
        ]);
    });

    it('calculates total_price as the sum of all item subtotals', function () {
        /** @var \Tests\TestCase $this */
        $productA = Product::factory()->create(['price' => 200.00]);
        $productB = Product::factory()->create(['price' => 75.00]);

        // Expected: (200 * 3) + (75 * 2) = 600 + 150 = 750.00
        $payload = [
            'customer_name'  => 'John Smith',
            'customer_email' => 'john@example.com',
            'items' => [
                ['product_id' => $productA->id, 'qty' => 3],
                ['product_id' => $productB->id, 'qty' => 2],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'john@example.com',
            'total_price'    => 750.00,
        ]);
    });

    it('snapshots the product price into order_items at the time of purchase', function () {
        /** @var \Tests\TestCase $this */
        $product = Product::factory()->create(['price' => 99.99]);

        $payload = [
            'customer_name'  => 'Alice',
            'customer_email' => 'alice@example.com',
            'items' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ];

        $this->postJson('/api/orders', $payload)->assertStatus(201);

        // Verify the snapshot price in order_items matches the product price at order time
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'qty'        => 1,
            'price'      => 99.99,
            'subtotal'   => 99.99,
        ]);

        // Change the product price — the stored snapshot must remain unchanged
        $product->update(['price' => 199.99]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'price'      => 99.99,
        ]);
    });

    it('rejects an order when one of the products is inactive', function () {
        /** @var \Tests\TestCase $this */
        $activeProduct   = Product::factory()->create(['price' => 50.00]);
        $inactiveProduct = Product::factory()->inactive()->create(['price' => 30.00]);

        $payload = [
            'customer_name'  => 'Bob',
            'customer_email' => 'bob@example.com',
            'items' => [
                ['product_id' => $activeProduct->id,   'qty' => 1],
                ['product_id' => $inactiveProduct->id,  'qty' => 2],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);

        // No order or order_items should have been persisted
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    });

    it('rejects an order when validation fields are missing', function () {
        /** @var \Tests\TestCase $this */
        $response = $this->postJson('/api/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_name', 'customer_email', 'items']);
    });

    it('rejects an order when a product_id does not exist', function () {
        /** @var \Tests\TestCase $this */
        $payload = [
            'customer_name'  => 'Ghost',
            'customer_email' => 'ghost@example.com',
            'items' => [
                ['product_id' => 99999, 'qty' => 1],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.product_id']);
    });

});
