<?php

namespace Tests\Unit\Models;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = ['name', 'email', 'status', 'avatar', 'password'];
        $user = new User();

        $this->assertEquals($fillable, $user->getFillable());
    }

    /** @test */
    public function it_casts_status_to_user_status_enum()
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assertInstanceOf(UserStatus::class, $user->status);
        $this->assertEquals(UserStatus::ACTIVE, $user->status);
    }

    /** @test */
    public function it_hashes_password_on_creation()
    {
        $user = User::factory()->create([
            'password' => 'plain-password',
        ]);

        $this->assertNotEquals('plain-password', $user->password);
        $this->assertTrue(\Hash::check('plain-password', $user->password));
    }

    /** @test */
    public function it_can_check_if_user_is_active()
    {
        $activeUser = User::factory()->create(['status' => UserStatus::ACTIVE]);
        $pendingUser = User::factory()->create(['status' => UserStatus::PENDING]);

        $this->assertTrue($activeUser->isActive());
        $this->assertFalse($pendingUser->isActive());
    }

    /** @test */
    public function it_can_check_if_user_is_pending()
    {
        $pendingUser = User::factory()->create(['status' => UserStatus::PENDING]);
        $activeUser = User::factory()->create(['status' => UserStatus::ACTIVE]);

        $this->assertTrue($pendingUser->isPending());
        $this->assertFalse($activeUser->isPending());
    }

    /** @test */
    public function it_can_check_if_user_is_suspended()
    {
        $suspendedUser = User::factory()->create(['status' => UserStatus::SUSPENDED]);
        $activeUser = User::factory()->create(['status' => UserStatus::ACTIVE]);

        $this->assertTrue($suspendedUser->isSuspended());
        $this->assertFalse($activeUser->isSuspended());
    }

    /** @test */
    public function it_can_scope_active_users()
    {
        User::factory()->create(['status' => UserStatus::ACTIVE]);
        User::factory()->create(['status' => UserStatus::PENDING]);
        User::factory()->create(['status' => UserStatus::ACTIVE]);

        $activeUsers = User::active()->get();

        $this->assertCount(2, $activeUsers);
        $this->assertTrue($activeUsers->every(fn($user) => $user->status === UserStatus::ACTIVE));
    }

    /** @test */
    public function it_can_scope_pending_users()
    {
        User::factory()->create(['status' => UserStatus::PENDING]);
        User::factory()->create(['status' => UserStatus::ACTIVE]);
        User::factory()->create(['status' => UserStatus::PENDING]);

        $pendingUsers = User::pending()->get();

        $this->assertCount(2, $pendingUsers);
        $this->assertTrue($pendingUsers->every(fn($user) => $user->status === UserStatus::PENDING));
    }

    /** @test */
    public function it_can_scope_suspended_users()
    {
        User::factory()->create(['status' => UserStatus::SUSPENDED]);
        User::factory()->create(['status' => UserStatus::ACTIVE]);

        $suspendedUsers = User::suspended()->get();

        $this->assertCount(1, $suspendedUsers);
        $this->assertEquals(UserStatus::SUSPENDED, $suspendedUsers->first()->status);
    }
}
