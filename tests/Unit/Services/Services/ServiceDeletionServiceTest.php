<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Services;

use Exception;
use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Services\Services\ServiceDeletionService;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServiceRepositoryInterface;

class ServiceDeletionServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Services\ServiceDeletionService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
        $this->repository = m::mock(ServiceRepositoryInterface::class);

        $this->service = new ServiceDeletionService($this->serverRepository, $this->repository);
    }

    /**
     * Test that a service is deleted when there are no servers attached to a service.
     */
    public function testServiceIsDeleted()
    {
        $this->serverRepository->shouldReceive('findCountWhere')->with([['service_id', '=', 1]])->once()->andReturn(0);
        $this->repository->shouldReceive('delete')->with(1)->once()->andReturn(1);

        $this->assertEquals(1, $this->service->handle(1));
    }

    /**
     * Test that an exception is thrown when there are servers attached to a service.
     *
     * @dataProvider serverCountProvider
     */
    public function testExceptionIsThrownIfServersAreAttached($count)
    {
        $this->serverRepository->shouldReceive('findCountWhere')->with([['service_id', '=', 1]])->once()->andReturn($count);

        try {
            $this->service->handle(1);
        } catch (Exception $exception) {
            $this->assertInstanceOf(HasActiveServersException::class, $exception);
            $this->assertEquals(trans('exceptions.service.delete_has_servers'), $exception->getMessage());
        }
    }

    /**
     * Provide assorted server counts to ensure that an exception is always thrown when more than 0 servers are found.
     *
     * @return array
     */
    public function serverCountProvider()
    {
        return [
            [1], [2], [5], [10],
        ];
    }
}
