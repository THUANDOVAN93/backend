<?php


namespace Tests\Feature\Requests;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateUserRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_allows_partial_updates()
    {
        $request = new UpdateUserRequest();

        $validator = Validator::make([
            'name' => 'Updated Name',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_validates_email_uniqueness_except_current_user()
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $request = new UpdateUserRequest();
        $request->setRouteResolver(function () use ($user1) {
            return new class($user1->ulid) {
                public function __construct(public $ulid)
                {
                }

                public function parameter($key)
                {
                    return $this->ulid;
                }
            };
        });

        $validator = Validator::make([
            'email' => 'user2@example.com',
        ], $request->rules());

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function it_allows_keeping_same_email()
    {
        $user = User::factory()->create(['email' => 'same@example.com']);

        $request = new UpdateUserRequest();
        $request->setRouteResolver(function () use ($user) {
            return new class($user->ulid) {
                public function __construct(public $ulid)
                {
                }

                public function parameter($key)
                {
                    return $this->ulid;
                }
            };
        });

        $validator = Validator::make([
            'email' => 'same@example.com',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }
}
