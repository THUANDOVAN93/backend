<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Assert validation errors in custom format
     */
    protected function assertValidationErrors(TestResponse $response, array $fields): void
    {
        $response->assertStatus(422);

        $errorNames = collect($response->json('errors'))->pluck('name')->toArray();

        foreach ($fields as $field) {
            $this->assertContains(
                $field,
                $errorNames,
                "Failed to find validation error for field: {$field}"
            );
        }
    }
}
