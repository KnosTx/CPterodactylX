<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Eloquent\AllocationRepository;
use Pterodactyl\Transformers\Api\Client\AllocationTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Services\Allocations\FindAssignableAllocationService;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\GetNetworkRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\NewAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\DeleteAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\UpdateAllocationRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Network\SetPrimaryAllocationRequest;

class NetworkAllocationController extends ClientApiController
{
    private AllocationRepository $repository;
    private ServerRepository $serverRepository;
    private FindAssignableAllocationService $assignableAllocationService;

    /**
     * NetworkController constructor.
     */
    public function __construct(
        AllocationRepository $repository,
        ServerRepository $serverRepository,
        FindAssignableAllocationService $assignableAllocationService
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
        $this->assignableAllocationService = $assignableAllocationService;
    }

    /**
     * Lists all of the allocations available to a server and whether or
     * not they are currently assigned as the primary for this server.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetNetworkRequest $request, Server $server): array
    {
        return $this->fractal->collection($server->allocations)
            ->transformWith(AllocationTransformer::class)
            ->toArray();
    }

    /**
     * Set the primary allocation for a server.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function update(UpdateAllocationRequest $request, Server $server, Allocation $allocation): array
    {
        $allocation = $this->repository->update($allocation->id, [
            'notes' => $request->input('notes'),
        ]);

        return $this->fractal->item($allocation)
            ->transformWith(AllocationTransformer::class)
            ->toArray();
    }

    /**
     * Set the primary allocation for a server.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function setPrimary(SetPrimaryAllocationRequest $request, Server $server, Allocation $allocation): array
    {
        $this->serverRepository->update($server->id, ['allocation_id' => $allocation->id]);

        return $this->fractal->item($allocation)
            ->transformWith(AllocationTransformer::class)
            ->toArray();
    }

    /**
     * Set the notes for the allocation for a server.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function store(NewAllocationRequest $request, Server $server): array
    {
        if ($server->allocations()->count() >= $server->allocation_limit) {
            throw new DisplayException('Cannot assign additional allocations to this server: limit has been reached.');
        }

        $allocation = $this->assignableAllocationService->handle($server);

        return $this->fractal->item($allocation)
            ->transformWith(AllocationTransformer::class)
            ->toArray();
    }

    /**
     * Delete an allocation from a server.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(DeleteAllocationRequest $request, Server $server, Allocation $allocation): Response
    {
        if ($allocation->id === $server->allocation_id) {
            throw new DisplayException('You cannot delete the primary allocation for this server.');
        }

        Allocation::query()->where('id', $allocation->id)->update([
            'notes' => null,
            'server_id' => null,
        ]);

        return $this->returnNoContent();
    }
}
