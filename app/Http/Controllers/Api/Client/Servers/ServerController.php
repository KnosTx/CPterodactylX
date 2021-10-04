<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\SubuserRepository;
use Pterodactyl\Transformers\Api\Client\ServerTransformer;
use Pterodactyl\Services\Servers\GetUserPermissionsService;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest;

class ServerController extends ClientApiController
{
    private SubuserRepository $repository;
    private GetUserPermissionsService $permissionsService;

    /**
     * ServerController constructor.
     */
    public function __construct(GetUserPermissionsService $permissionsService, SubuserRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
        $this->permissionsService = $permissionsService;
    }

    /**
     * Transform an individual server into a response that can be consumed by a
     * client using the API.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetServerRequest $request, Server $server): array
    {
        return $this->fractal->item($server)
            ->transformWith(ServerTransformer::class)
            ->addMeta([
                'is_server_owner' => $request->user()->id === $server->owner_id,
                'user_permissions' => $this->permissionsService->handle($server, $request->user()),
            ])
            ->toArray();
    }
}
