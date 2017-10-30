<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Schedules;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Schedule;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Schedules\ScheduleCreationService;
use Pterodactyl\Services\Schedules\Tasks\TaskCreationService;
use Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface;

class ScheduleCreationServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Cron\CronExpression
     */
    protected $cron;

    /**
     * @var \Pterodactyl\Contracts\Repository\ScheduleRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Schedules\ScheduleCreationService
     */
    protected $service;

    /**
     * @var \Pterodactyl\Services\Schedules\Tasks\TaskCreationService
     */
    protected $taskCreationService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->connection = m::mock(ConnectionInterface::class);
        $this->cron = m::mock('overload:\Cron\CronExpression');
        $this->repository = m::mock(ScheduleRepositoryInterface::class);
        $this->taskCreationService = m::mock(TaskCreationService::class);

        $this->service = new ScheduleCreationService($this->connection, $this->repository, $this->taskCreationService);
    }

    /**
     * Test that a schedule with no tasks can be created.
     */
    public function testScheduleWithNoTasksIsCreated()
    {
        $schedule = factory(Schedule::class)->make();
        $server = factory(Server::class)->make();

        $this->cron->shouldReceive('factory')->with('* * * * * *')->once()->andReturnSelf()
            ->shouldReceive('getNextRunDate')->withNoArgs()->once()->andReturn('nextDate');
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('create')->with([
            'server_id' => $server->id,
            'next_run_at' => 'nextDate',
            'test_data' => 'test_value',
        ])->once()->andReturn($schedule);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle($server, ['test_data' => 'test_value']);
        $this->assertNotEmpty($response);
        $this->assertInstanceOf(Schedule::class, $response);
        $this->assertEquals($schedule, $response);
    }

    /**
     * Test that a schedule with at least one task can be created.
     */
    public function testScheduleWithTasksIsCreated()
    {
        $schedule = factory(Schedule::class)->make();
        $server = factory(Server::class)->make();

        $this->cron->shouldReceive('factory')->with('* * * * * *')->once()->andReturnSelf()
            ->shouldReceive('getNextRunDate')->withNoArgs()->once()->andReturn('nextDate');
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('create')->with([
            'next_run_at' => 'nextDate',
            'server_id' => $server->id,
            'test_data' => 'test_value',
        ])->once()->andReturn($schedule);
        $this->taskCreationService->shouldReceive('handle')->with($schedule, [
            'time_interval' => 'm',
            'time_value' => 10,
            'sequence_id' => 1,
            'action' => 'test',
            'payload' => 'testpayload',
        ], false)->once()->andReturnNull();

        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle($server, ['test_data' => 'test_value'], [
            ['time_interval' => 'm', 'time_value' => 10, 'action' => 'test', 'payload' => 'testpayload'],
        ]);
        $this->assertNotEmpty($response);
        $this->assertInstanceOf(Schedule::class, $response);
        $this->assertEquals($schedule, $response);
    }

    /**
     * Test that an ID can be passed in place of the server model.
     */
    public function testIdCanBePassedInPlaceOfServerModel()
    {
        $schedule = factory(Schedule::class)->make();

        $this->cron->shouldReceive('factory')->with('* * * * * *')->once()->andReturnSelf()
            ->shouldReceive('getNextRunDate')->withNoArgs()->once()->andReturn('nextDate');
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('create')->with([
            'next_run_at' => 'nextDate',
            'server_id' => 1234,
            'test_data' => 'test_value',
        ])->once()->andReturn($schedule);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle(1234, ['test_data' => 'test_value']);
        $this->assertNotEmpty($response);
        $this->assertInstanceOf(Schedule::class, $response);
        $this->assertEquals($schedule, $response);
    }

    /**
     * Test that an exception is raised if invalid data is passed.
     *
     * @dataProvider invalidServerArgumentProvider
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionIsThrownIfServerIsInvalid($attribute)
    {
        $this->service->handle($attribute, []);
    }

    /**
     * Return an array of invalid server data to test aganist.
     *
     * @return array
     */
    public function invalidServerArgumentProvider()
    {
        return [
            [123.456],
            ['server'],
            ['abc123'],
            ['123_test'],
            [new Node()],
            [Server::class],
        ];
    }
}
