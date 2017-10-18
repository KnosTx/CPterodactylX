<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Subusers;

use Illuminate\Log\Writer;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Contracts\Repository\PermissionRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class SubuserUpdateService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonRepository;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService
     */
    private $keyProviderService;

    /**
     * @var \Pterodactyl\Contracts\Repository\PermissionRepositoryInterface
     */
    protected $permissionRepository;

    /**
     * @var \Pterodactyl\Services\Subusers\PermissionCreationService
     */
    protected $permissionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * SubuserUpdateService constructor.
     *
     * @param \Illuminate\Database\ConnectionInterface                           $connection
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService          $keyProviderService
     * @param \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface $daemonRepository
     * @param \Pterodactyl\Services\Subusers\PermissionCreationService           $permissionService
     * @param \Pterodactyl\Contracts\Repository\PermissionRepositoryInterface    $permissionRepository
     * @param \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface       $repository
     * @param \Illuminate\Log\Writer                                             $writer
     */
    public function __construct(
        ConnectionInterface $connection,
        DaemonKeyProviderService $keyProviderService,
        DaemonServerRepositoryInterface $daemonRepository,
        PermissionCreationService $permissionService,
        PermissionRepositoryInterface $permissionRepository,
        SubuserRepositoryInterface $repository,
        Writer $writer
    ) {
        $this->connection = $connection;
        $this->daemonRepository = $daemonRepository;
        $this->keyProviderService = $keyProviderService;
        $this->permissionRepository = $permissionRepository;
        $this->permissionService = $permissionService;
        $this->repository = $repository;
        $this->writer = $writer;
    }

    /**
     * Update permissions for a given subuser.
     *
     * @param int   $subuser
     * @param array $permissions
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($subuser, array $permissions)
    {
        $subuser = $this->repository->getWithServer($subuser);

        $this->connection->beginTransaction();
        $this->permissionRepository->deleteWhere([['subuser_id', '=', $subuser->id]]);
        $this->permissionService->handle($subuser->id, $permissions);

        try {
            $token = $this->keyProviderService->handle($subuser->server_id, $subuser->user_id, false);
            $this->daemonRepository->setNode($subuser->server->node_id)->revokeAccessKey($token);
        } catch (RequestException $exception) {
            $this->connection->rollBack();
            $this->writer->warning($exception);

            $response = $exception->getResponse();
            throw new DisplayException(trans('exceptions.daemon_connection_failed', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }

        $this->connection->commit();
    }
}
