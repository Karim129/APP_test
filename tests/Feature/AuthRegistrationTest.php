<?php

use App\Models\User;
use App\Models\Role;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    // Seed roles before each test
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    $this->authService = app(AuthService::class);
});

// Feature: user-authentication-roles, Property 1: Valid registration creates user with default role
test('valid registration creates user with default role', function () {
    $email = fake()->unique()->safeEmail();
    $password = fake()->password(8);
    $name = fake()->name();

    $user = $this->authService->register([
        'email' => $email,
        'password' => $password,
        'name' => $name,
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->email)->toBe($email)
        ->and($user->name)->toBe($name)
        ->and($user->roles->pluck('name')->toArray())->toContain('Regular User');
})->repeat(100);

// Feature: user-authentication-roles, Property 2: Duplicate email rejection
test('duplicate email registration is rejected', function () {
    $email = fake()->unique()->safeEmail();
    $password = fake()->password(8);
    $name = fake()->name();

    // Register first user
    $user1 = $this->authService->register([
        'email' => $email,
        'password' => $password,
        'name' => $name,
    ]);

    expect($user1)->toBeInstanceOf(User::class);

    // Attempt to register second user with same email
    try {
        $this->authService->register([
            'email' => $email,
            'password' => fake()->password(8),
            'name' => fake()->name(),
        ]);

        // If we reach here, the test should fail
        expect(false)->toBeTrue('Expected ValidationException to be thrown');
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Expected behavior - duplicate email should be rejected
        expect($e->errors())->toHaveKey('email');
    }
})->repeat(100);

// Feature: user-authentication-roles, Property 4: Password hashing
test('passwords are always hashed', function () {
    $email = fake()->unique()->safeEmail();
    $password = fake()->password(8);
    $name = fake()->name();

    $user = $this->authService->register([
        'email' => $email,
        'password' => $password,
        'name' => $name,
    ]);

    // Password should not be stored in plaintext
    expect($user->password)->not->toBe($password)
        // Password should be hashed using bcrypt or Argon2
        ->and(Hash::check($password, $user->password))->toBeTrue();
})->repeat(100);
