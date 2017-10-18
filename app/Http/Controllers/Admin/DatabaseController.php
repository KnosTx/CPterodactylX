<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Admin;

use Pterodactyl\Models\DatabaseHost;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Database\DatabaseHostService;
use Pterodactyl\Http\Requests\Admin\DatabaseHostFormRequest;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;

class DatabaseController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Database\DatabaseHostService
     */
    protected $service;

    /**
     * DatabaseController constructor.
     *
     * @param \Prologue\Alerts\AlertsMessageBag                                 $alert
     * @param \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface $repository
     * @param \Pterodactyl\Services\Database\DatabaseHostService                $service
     * @param \Pterodactyl\Contracts\Repository\LocationRepositoryInterface     $locationRepository
     */
    public function __construct(
        AlertsMessageBag $alert,
        DatabaseHostRepositoryInterface $repository,
        DatabaseHostService $service,
        LocationRepositoryInterface $locationRepository
    ) {
        $this->alert = $alert;
        $this->repository = $repository;
        $this->service = $service;
        $this->locationRepository = $locationRepository;
    }

    /**
     * Display database host index.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.databases.index', [
            'locations' => $this->locationRepository->getAllWithNodes(),
            'hosts' => $this->repository->getWithViewDetails(),
        ]);
    }

    /**
     * Display database host to user.
     *
     * @param int $host
     * @return \Illuminate\View\View
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function view($host)
    {
        return view('admin.databases.view', [
            'locations' => $this->locationRepository->getAllWithNodes(),
            'host' => $this->repository->getWithServers($host),
        ]);
    }

    /**
     * Handle request to create a new database host.
     *
     * @param \Pterodactyl\Http\Requests\Admin\DatabaseHostFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function create(DatabaseHostFormRequest $request)
    {
        try {
            $host = $this->service->create($request->normalize());
            $this->alert->success('Successfully created a new database host on the system.')->flash();

            return redirect()->route('admin.databases.view', $host->id);
        } catch (\PDOException $ex) {
            $this->alert->danger($ex->getMessage())->flash();
        }

        return redirect()->route('admin.databases');
    }

    /**
     * Handle updating database host.
     *
     * @param \Pterodactyl\Http\Requests\Admin\DatabaseHostFormRequest $request
     * @param \Pterodactyl\Models\DatabaseHost                         $host
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function update(DatabaseHostFormRequest $request, DatabaseHost $host)
    {
        if ($request->input('action') === 'delete') {
            return $this->delete($host);
        }

        try {
            $host = $this->service->update($host->id, $request->normalize());
            $this->alert->success('Database host was updated successfully.')->flash();
        } catch (\PDOException $ex) {
            $this->alert->danger($ex->getMessage())->flash();
        }

        return redirect()->route('admin.databases.view', $host->id);
    }

    /**
     * Handle request to delete a database host.
     *
     * @param \Pterodactyl\Models\DatabaseHost $host
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function delete(DatabaseHost $host)
    {
        $this->service->delete($host->id);
        $this->alert->success('The requested database host has been deleted from the system.')->flash();

        return redirect()->route('admin.databases');
    }
}
