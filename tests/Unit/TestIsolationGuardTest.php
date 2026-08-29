<?php

namespace Tests\Unit;

use App\Support\Testing\TestIsolationGuard;
use RuntimeException;
use Tests\TestCase;

class TestIsolationGuardTest extends TestCase
{
    public function test_guard_rejects_the_real_application_sqlite_database(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('storage/framework/testing');

        TestIsolationGuard::assertDatabase([
            'driver' => 'sqlite',
            'sqlite_path' => database_path('database.sqlite'),
        ]);
    }

    public function test_guard_allows_memory_and_uniquely_named_disposable_databases(): void
    {
        TestIsolationGuard::assertDatabase([
            'driver' => 'sqlite',
            'sqlite_path' => ':memory:',
        ]);

        TestIsolationGuard::assertDatabase([
            'driver' => 'sqlite',
            'sqlite_path' => storage_path('framework/testing/test_guard_example.sqlite'),
        ]);

        $this->addToAssertionCount(2);
    }

    public function test_guard_rejects_non_test_server_database_names(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('explicitly named test database');

        TestIsolationGuard::assertDatabase([
            'driver' => 'mysql',
            'database' => 'ecommerce_store',
        ]);
    }
}
