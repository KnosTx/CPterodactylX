<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Administrative;

use Mockery as m;
use Tests\TestCase;
use Illuminate\Database\DatabaseManager;
use Pterodactyl\Exceptions\DisplayException;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Pterodactyl\Services\Database\DatabaseHostService;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;

class DatabaseHostServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    protected $databaseRepository;

    /**
     * @var \Pterodactyl\Extensions\DynamicDatabaseConnection
     */
    protected $dynamic;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Database\DatabaseHostService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->database = m::mock(DatabaseManager::class);
        $this->databaseRepository = m::mock(DatabaseRepositoryInterface::class);
        $this->dynamic = m::mock(DynamicDatabaseConnection::class);
        $this->encrypter = m::mock(Encrypter::class);
        $this->repository = m::mock(DatabaseHostRepositoryInterface::class);

        $this->service = new DatabaseHostService(
            $this->database,
            $this->databaseRepository,
            $this->repository,
            $this->dynamic,
            $this->encrypter
        );
    }

    /**
     * Test that creating a host returns the correct data.
     */
    public function testHostIsCreated()
    {
        $data = [
            'password' => 'raw-password',
            'name' => 'HostName',
            'host' => '127.0.0.1',
            'port' => 3306,
            'username' => 'someusername',
            'node_id' => null,
        ];

        $finalData = (object) array_replace($data, ['password' => 'enc-password']);

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->encrypter->shouldReceive('encrypt')->with('raw-password')->once()->andReturn('enc-password');

        $this->repository->shouldReceive('create')->with([
            'password' => 'enc-password',
            'name' => 'HostName',
            'host' => '127.0.0.1',
            'port' => 3306,
            'username' => 'someusername',
            'max_databases' => null,
            'node_id' => null,
        ])->once()->andReturn($finalData);

        $this->dynamic->shouldReceive('set')->with('dynamic', $finalData)->once()->andReturnNull();
        $this->database->shouldReceive('connection')->with('dynamic')->once()->andReturnSelf()
            ->shouldReceive('select')->with('SELECT 1 FROM dual')->once()->andReturnNull();

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->create($data);

        $this->assertNotNull($response);
        $this->assertTrue(is_object($response), 'Assert that response is an object.');

        $this->assertEquals('enc-password', $response->password);
        $this->assertEquals('HostName', $response->name);
        $this->assertEquals('127.0.0.1', $response->host);
        $this->assertEquals(3306, $response->port);
        $this->assertEquals('someusername', $response->username);
        $this->assertNull($response->node_id);
    }

    /**
     * Test that passing a password will store an encrypted version in the DB.
     */
    public function testHostIsUpdatedWithPasswordProvided()
    {
        $finalData = (object) ['password' => 'enc-pass', 'host' => '123.456.78.9'];

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->encrypter->shouldReceive('encrypt')->with('raw-pass')->once()->andReturn('enc-pass');

        $this->repository->shouldReceive('update')->with(1, [
            'password' => 'enc-pass',
            'host' => '123.456.78.9',
        ])->once()->andReturn($finalData);

        $this->dynamic->shouldReceive('set')->with('dynamic', $finalData)->once()->andReturnNull();
        $this->database->shouldReceive('connection')->with('dynamic')->once()->andReturnSelf()
            ->shouldReceive('select')->with('SELECT 1 FROM dual')->once()->andReturnNull();

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->update(1, ['password' => 'raw-pass', 'host' => '123.456.78.9']);

        $this->assertNotNull($response);
        $this->assertEquals('enc-pass', $response->password);
        $this->assertEquals('123.456.78.9', $response->host);
    }

    /**
     * Test that passing no or empty password will skip storing it.
     */
    public function testHostIsUpdatedWithoutPassword()
    {
        $finalData = (object) ['host' => '123.456.78.9'];

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->encrypter->shouldNotReceive('encrypt');

        $this->repository->shouldReceive('update')->with(1, ['host' => '123.456.78.9'])->once()->andReturn($finalData);

        $this->dynamic->shouldReceive('set')->with('dynamic', $finalData)->once()->andReturnNull();
        $this->database->shouldReceive('connection')->with('dynamic')->once()->andReturnSelf()
            ->shouldReceive('select')->with('SELECT 1 FROM dual')->once()->andReturnNull();

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->update(1, ['password' => '', 'host' => '123.456.78.9']);

        $this->assertNotNull($response);
        $this->assertEquals('123.456.78.9', $response->host);
    }

    /**
     * Test that a database host can be deleted.
     */
    public function testHostIsDeleted()
    {
        $this->databaseRepository->shouldReceive('findCountWhere')->with([['database_host_id', '=', 1]])->once()->andReturn(0);
        $this->repository->shouldReceive('delete')->with(1)->once()->andReturn(true);

        $response = $this->service->delete(1);

        $this->assertTrue($response, 'Assert that response is true.');
    }

    /**
     * Test exception is thrown when there are databases attached to a host.
     */
    public function testExceptionIsThrownIfHostHasDatabases()
    {
        $this->databaseRepository->shouldReceive('findCountWhere')->with([['database_host_id', '=', 1]])->once()->andReturn(2);

        try {
            $this->service->delete(1);
        } catch (DisplayException $exception) {
            $this->assertEquals(trans('exceptions.databases.delete_has_databases'), $exception->getMessage());
        }
    }
}
