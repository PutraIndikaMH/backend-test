<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Enums\RoleEnum;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

describe('POST /api/login', function () {

    it('returns 200 with data.token and data.user on valid credentials', function () {
        /** @var \Tests\TestCase $this */
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
            'role'     => RoleEnum::User,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email', 'role', 'created_at'],
                ],
            ])
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'test@example.com')
            ->assertJsonPath('data.user.role', 'user');

        expect($response->json('data.token'))->not->toBeEmpty();
    });

    it('returns 422 with error on email field when password is wrong', function () {
        /** @var \Tests\TestCase $this */
        User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('returns 422 with validation errors when fields are missing', function () {
        /** @var \Tests\TestCase $this */
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    });

});

describe('POST /api/logout', function () {

    it('returns 200 and deletes the token from personal_access_tokens', function () {
        /** @var \Tests\TestCase $this */
        $user        = User::factory()->create();
        $tokenResult = $user->createToken('api-token');
        $plainText   = $tokenResult->plainTextToken;

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        $response = $this->withToken($plainText)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Successfully logged out.');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenResult->accessToken->id,
        ]);
    });

    it('returns 401 when no token is provided', function () {
        /** @var \Tests\TestCase $this */
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    });

});
