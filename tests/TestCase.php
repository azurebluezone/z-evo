<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Laravel 10 resolves Schema::getColumnType() through Doctrine DBAL,
        // and DBAL has no mapping for MySQL ENUM by default. campaigns
        // .campaign_type is an ENUM, so any schema introspection of that table
        // throws "Unknown database type enum requested". Map it once per test
        // connection so introspection works without touching production boot.
        try {
            Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->getDatabasePlatform()
                ->registerDoctrineTypeMapping('enum', 'string');
        } catch (\Throwable $e) {
            // A driver without Doctrine support does not need the mapping.
        }
    }
}
