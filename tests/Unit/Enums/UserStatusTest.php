<?php


namespace Tests\Unit\Enums;

use App\Enums\UserStatus;
use Tests\TestCase;

class UserStatusTest extends TestCase
{
    /** @test */
    public function it_has_correct_values()
    {
        $this->assertEquals('active', UserStatus::ACTIVE->value);
        $this->assertEquals('pending', UserStatus::PENDING->value);
        $this->assertEquals('suspended', UserStatus::SUSPENDED->value);
        $this->assertEquals('inactive', UserStatus::INACTIVE->value);
    }

    /** @test */
    public function it_returns_correct_labels()
    {
        $this->assertEquals('Active', UserStatus::ACTIVE->label());
        $this->assertEquals('Pending', UserStatus::PENDING->label());
        $this->assertEquals('Suspended', UserStatus::SUSPENDED->label());
        $this->assertEquals('Inactive', UserStatus::INACTIVE->label());
    }

    /** @test */
    public function it_returns_correct_colors()
    {
        $this->assertEquals('green', UserStatus::ACTIVE->color());
        $this->assertEquals('yellow', UserStatus::PENDING->color());
        $this->assertEquals('red', UserStatus::SUSPENDED->color());
        $this->assertEquals('gray', UserStatus::INACTIVE->color());
    }

    /** @test */
    public function it_returns_all_values()
    {
        $values = UserStatus::values();

        $this->assertCount(4, $values);
        $this->assertContains('active', $values);
        $this->assertContains('pending', $values);
        $this->assertContains('suspended', $values);
        $this->assertContains('inactive', $values);
    }

    /** @test */
    public function it_returns_options_array()
    {
        $options = UserStatus::options();

        $this->assertCount(4, $options);
        $this->assertArrayHasKey('value', $options[0]);
        $this->assertArrayHasKey('label', $options[0]);
        $this->assertArrayHasKey('color', $options[0]);
    }
}
