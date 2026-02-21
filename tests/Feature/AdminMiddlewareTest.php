<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Enums\RoleEnum;
use App\Models\User;

// Uses GET /api/orders as a representative admin-only route

describe('AdminMiddleware', function () {

    it('returns 403 with correct message when authenticated user has user role', function () {
        /** @var \Tests\TestCase $this */
        $user = User::factory()->create(['role' => RoleEnum::User]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden. Admin access required.',
            ]);
    });

    it('passes through successfully when authenticated user has admin role', function () {
        /** @var \Tests\TestCase $this */
        $admin = User::factory()->create(['role' => RoleEnum::Admin]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(200);
    });

    it('returns 401 when the request is unauthenticated', function () {
        /** @var \Tests\TestCase $this */
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
    });

});
