<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services;

use Mockery as m;
use Tests\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Notifications\ChannelManager;
use Pterodactyl\Notifications\AccountCreated;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Services\Helpers\TemporaryPasswordService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;

class UserCreationServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $appMock;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $database;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * @var \Illuminate\Notifications\ChannelManager
     */
    protected $notification;

    /**
     * @var \Pterodactyl\Services\Helpers\TemporaryPasswordService
     */
    protected $passwordService;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Users\UserCreationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->appMock = m::mock(Application::class);
        $this->database = m::mock(ConnectionInterface::class);
        $this->hasher = m::mock(Hasher::class);
        $this->notification = m::mock(ChannelManager::class);
        $this->passwordService = m::mock(TemporaryPasswordService::class);
        $this->repository = m::mock(UserRepositoryInterface::class);

        $this->service = new UserCreationService(
            $this->appMock,
            $this->notification,
            $this->database,
            $this->hasher,
            $this->passwordService,
            $this->repository
        );
    }

    /**
     * Test that a user is created when a password is passed.
     */
    public function testUserIsCreatedWhenPasswordIsProvided()
    {
        $user = (object) [
            'name_first' => 'FirstName',
            'username' => 'user_name',
        ];

        $this->hasher->shouldReceive('make')->with('raw-password')->once()->andReturn('enc-password');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->hasher->shouldNotReceive('make');
        $this->passwordService->shouldNotReceive('handle');
        $this->repository->shouldReceive('create')->with(['password' => 'enc-password'])->once()->andReturn($user);
        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();
        $this->appMock->shouldReceive('makeWith')->with(AccountCreated::class, [
            'user' => [
                'name' => 'FirstName',
                'username' => 'user_name',
                'token' => null,
            ],
        ])->once()->andReturnNull();

        $this->notification->shouldReceive('send')->with($user, null)->once()->andReturnNull();

        $response = $this->service->handle([
            'password' => 'raw-password',
        ]);

        $this->assertNotNull($response);
        $this->assertEquals($user->username, $response->username);
        $this->assertEquals($user->name_first, 'FirstName');
    }

    /**
     * Test that a user is created with a random password when no password is provided.
     */
    public function testUserIsCreatedWhenNoPasswordIsProvided()
    {
        $user = (object) [
            'name_first' => 'FirstName',
            'username' => 'user_name',
            'email' => 'user@example.com',
        ];

        $this->hasher->shouldNotReceive('make');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->hasher->shouldReceive('make')->once()->andReturn('created-enc-password');
        $this->passwordService->shouldReceive('handle')
            ->with('user@example.com')
            ->once()
            ->andReturn('random-token');

        $this->repository->shouldReceive('create')->with([
            'password' => 'created-enc-password',
            'email' => 'user@example.com',
        ])->once()->andReturn($user);

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();
        $this->appMock->shouldReceive('makeWith')->with(AccountCreated::class, [
            'user' => [
                'name' => 'FirstName',
                'username' => 'user_name',
                'token' => 'random-token',
            ],
        ])->once()->andReturnNull();

        $this->notification->shouldReceive('send')->with($user, null)->once()->andReturnNull();

        $response = $this->service->handle([
            'email' => 'user@example.com',
        ]);

        $this->assertNotNull($response);
        $this->assertEquals($user->username, $response->username);
        $this->assertEquals($user->name_first, 'FirstName');
        $this->assertEquals($user->email, $response->email);
    }
}
