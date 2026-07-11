<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Twilio\Rest\Client;

abstract class TestCase extends BaseTestCase
{
    protected function mockTwilio(): void
    {
        $this->app->singleton(Client::class, function () {
            $mock = \Mockery::mock(Client::class);
            $mockMessages = \Mockery::mock();
            $mock->messages = $mockMessages;
            $mockMessages->shouldReceive('create')->once();
            return $mock;
        });
    }

    protected function mockTwilioWithException(): void
    {
        $this->app->singleton(Client::class, function () {
            $mock = \Mockery::mock(Client::class);
            $mockMessages = \Mockery::mock();
            $mock->messages = $mockMessages;
            $mockMessages->shouldReceive('create')->andThrow(new \Exception('SMS failed'));
            return $mock;
        });
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
