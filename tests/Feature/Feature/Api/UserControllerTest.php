<?php


namespace Tests\Feature\Api;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $authenticatedUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([VerifyCsrfToken::class]);

        // Create a user explicitly
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'status' => UserStatus::ACTIVE,
        ]);

        $this->authenticatedUser = $user;

        // Authenticate using Sanctum
        Sanctum::actingAs($this->authenticatedUser, ['*']);
    }

    /** @test */
    public function it_can_list_users()
    {
        User::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['ulid', 'name', 'email', 'status', 'roles', 'created_at']
                ],
                'current_page',
                'last_page',
                'per_page',
                'total'
            ]);
    }

    /** @test */
    public function it_can_filter_users_by_search()
    {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $response = $this->getJson('/api/v1/users?search=John');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('John Doe', $response->json('data.0.name'));
    }

    /** @test */
    public function it_can_filter_users_by_status()
    {
        User::factory()->create(['status' => UserStatus::ACTIVE]);
        User::factory()->create(['status' => UserStatus::PENDING]);
        User::factory()->create(['status' => UserStatus::ACTIVE]);

        $response = $this->getJson('/api/v1/users?status=active');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_can_filter_users_by_role()
    {
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);

        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $user = User::factory()->create();
        $user->assignRole($userRole);

        $response = $this->getJson('/api/v1/users?role=admin');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function it_can_create_a_user()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['ulid', 'name', 'email', 'status', 'roles']
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_can_create_a_user_with_roles()
    {
        $role = Role::create(['name' => 'admin']);

        $userData = [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'roles' => ['admin'],
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertStatus(201);

        $user = User::where('email', 'admin@example.com')->first();
        $this->assertTrue($user->hasRole('admin'));
    }

    /** @test */
    public function it_validates_required_fields_when_creating_user()
    {
        $response = $this->postJson('/api/v1/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function it_validates_unique_email_when_creating_user()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_validates_password_minimum_length_when_creating_user()
    {
        $response = $this->postJson('/api/v1/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_validates_status_enum_when_creating_user()
    {
        $response = $this->postJson('/api/v1/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'status' => 'invalid-status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_can_show_a_user()
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/v1/users/{$user->ulid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['ulid', 'name', 'email', 'status', 'roles']
            ])
            ->assertJson([
                'user' => [
                    'ulid' => $user->ulid,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_when_user_not_found()
    {
        $response = $this->getJson('/api/v1/users/non-existent-ulid');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_update_a_user()
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ];

        $response = $this->putJson("/api/v1/users/{$user->ulid}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => ['ulid', 'name', 'email', 'status', 'roles']
            ]);

        $this->assertDatabaseHas('users', [
            'ulid' => $user->ulid,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    /** @test */
    public function it_can_update_user_password()
    {
        $user = User::factory()->create();

        $response = $this->putJson("/api/v1/users/{$user->ulid}", [
            'password' => 'newpassword123',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertTrue(\Hash::check('newpassword123', $user->password));
    }

    /** @test */
    public function it_can_update_user_roles()
    {
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);

        $user = User::factory()->create();
        $user->assignRole($userRole);

        $response = $this->putJson("/api/v1/users/{$user->ulid}", [
            'roles' => ['admin'],
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('user'));
    }

    /** @test */
    public function it_validates_unique_email_when_updating_user()
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $response = $this->putJson("/api/v1/users/{$user1->ulid}", [
            'email' => 'user2@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_allows_keeping_same_email_when_updating_user()
    {
        $user = User::factory()->create(['email' => 'same@example.com']);

        $response = $this->putJson("/api/v1/users/{$user->ulid}", [
            'name' => 'Updated Name',
            'email' => 'same@example.com',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_update_user_status()
    {
        $user = User::factory()->create(['status' => UserStatus::ACTIVE]);

        $response = $this->patchJson("/api/v1/users/{$user->ulid}/status", [
            'status' => 'suspended',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User status updated successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'ulid' => $user->ulid,
            'status' => 'suspended',
        ]);
    }

    /** @test */
    public function it_validates_status_when_updating_user_status()
    {
        $user = User::factory()->create();

        $response = $this->patchJson("/api/v1/users/{$user->ulid}/status", [
            'status' => 'invalid-status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_requires_status_when_updating_user_status()
    {
        $user = User::factory()->create();

        $response = $this->patchJson("/api/v1/users/{$user->ulid}/status", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_can_delete_a_user()
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/v1/users/{$user->ulid}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User deleted successfully'
            ]);

        $this->assertDatabaseMissing('users', [
            'ulid' => $user->ulid,
        ]);
    }

    /** @test */
    public function it_prevents_user_from_deleting_themselves()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/users/{$user->ulid}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You cannot delete yourself'
            ]);

        $this->assertDatabaseHas('users', [
            'ulid' => $user->ulid,
        ]);
    }

    /** @test */
    public function it_can_get_user_statuses()
    {
        $response = $this->getJson('/api/v1/user-statuses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'statuses' => [
                    '*' => ['value', 'label', 'color']
                ]
            ]);

        $statuses = $response->json('statuses');
        $this->assertCount(4, $statuses);
    }
}
