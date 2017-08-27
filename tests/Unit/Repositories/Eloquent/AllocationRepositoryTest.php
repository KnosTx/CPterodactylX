<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Tests\Unit\Repositories\Eloquent;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Allocation;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Repositories\Eloquent\AllocationRepository;

class AllocationRepositoryTest extends TestCase
{
    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $builder;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\AllocationRepository
     */
    protected $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->builder = m::mock(Builder::class);
        $this->repository = m::mock(AllocationRepository::class)->makePartial();

        $this->repository->shouldReceive('getBuilder')->withNoArgs()->andReturn($this->builder);
    }

    /**
     * Test that we are returning the correct model.
     */
    public function testCorrectModelIsAssigned()
    {
        $this->assertEquals(Allocation::class, $this->repository->model());
    }

    /**
     * Test that allocations can be assigned to a server correctly.
     */
    public function testAllocationsAreAssignedToAServer()
    {
        $this->builder->shouldReceive('whereIn')->with('id', [1, 2])->once()->andReturnSelf()
            ->shouldReceive('update')->with(['server_id' => 10])->once()->andReturn(true);

        $this->assertTrue($this->repository->assignAllocationsToServer(10, [1, 2]));
    }

    /**
     * Test that allocations with a node relationship are returned.
     */
    public function testAllocationsForANodeAreReturned()
    {
        $this->builder->shouldReceive('where')->with('node_id', 1)->once()->andReturnSelf()
            ->shouldReceive('get')->once()->andReturn(factory(Allocation::class)->make());

        $this->assertInstanceOf(Allocation::class, $this->repository->getAllocationsForNode(1));
    }
}
