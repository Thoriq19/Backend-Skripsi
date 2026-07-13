<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function migrateFreshParameters()
    {
        return [
            '--path' => realpath(__DIR__ . '/../../database/migrations'),
        ];
    }
}
