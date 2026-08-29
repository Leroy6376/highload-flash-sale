<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_user_model_defines_expected_casts(): void
    {
        $casts = (new User())->getCasts();

        self::assertSame('datetime', $casts['email_verified_at']);
        self::assertSame('hashed', $casts['password']);
    }
}
