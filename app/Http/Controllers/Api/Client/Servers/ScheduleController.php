<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Helpers\Utilities;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Eloquent\ScheduleRepository;
use Pterodactyl\Services\Schedules\ProcessScheduleService;
use Pterodactyl\Transformers\Api\Client\ScheduleTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\ViewScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\StoreScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\DeleteScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\UpdateScheduleRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Schedules\TriggerScheduleRequest;

class ScheduleController extends ClientApiController
{
    private ScheduleRepository $repository;
    private ProcessScheduleService $service;

    /**
     * ScheduleController constructor.
     */
    public function __construct(ScheduleRepository $repository, ProcessScheduleService $service)
    {
        parent::__construct();

        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * Returns all of the schedules belonging to a given server.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(ViewScheduleRequest $request, Server $server): array
    {
        $schedules = $server->schedule;
        $schedules->loadMissing('tasks');

        return $this->fractal->collection($schedules)
            ->transformWith(ScheduleTransformer::class)
            ->toArray();
    }

    /**
     * Store a new schedule for a server.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function store(StoreScheduleRequest $request, Server $server): array
    {
        /** @var \Pterodactyl\Models\Schedule $model */
        $model = $this->repository->create([
            'server_id' => $server->id,
            'name' => $request->input('name'),
            'cron_day_of_week' => $request->input('day_of_week'),
            'cron_month' => $request->input('month'),
            'cron_day_of_month' => $request->input('day_of_month'),
            'cron_hour' => $request->input('hour'),
            'cron_minute' => $request->input('minute'),
            'is_active' => (bool) $request->input('is_active'),
            'only_when_online' => (bool) $request->input('only_when_online'),
            'next_run_at' => $this->getNextRunAt($request),
        ]);

        return $this->fractal->item($model)
            ->transformWith(ScheduleTransformer::class)
            ->toArray();
    }

    /**
     * Returns a specific schedule for the server.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(ViewScheduleRequest $request, Server $server, Schedule $schedule): array
    {
        $schedule->loadMissing('tasks');

        return $this->fractal->item($schedule)
            ->transformWith(ScheduleTransformer::class)
            ->toArray();
    }

    /**
     * Updates a given schedule with the new data provided.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function update(UpdateScheduleRequest $request, Server $server, Schedule $schedule): array
    {
        $active = (bool) $request->input('is_active');

        $data = [
            'name' => $request->input('name'),
            'cron_day_of_week' => $request->input('day_of_week'),
            'cron_month' => $request->input('month'),
            'cron_day_of_month' => $request->input('day_of_month'),
            'cron_hour' => $request->input('hour'),
            'cron_minute' => $request->input('minute'),
            'is_active' => $active,
            'only_when_online' => (bool) $request->input('only_when_online'),
            'next_run_at' => $this->getNextRunAt($request),
        ];

        // Toggle the processing state of the scheduled task when it is enabled or disabled so that an
        // invalid state can be reset without manual database intervention.
        //
        // @see https://github.com/pterodactyl/panel/issues/2425
        if ($schedule->is_active !== $active) {
            $data['is_processing'] = false;
        }

        $this->repository->update($schedule->id, $data);

        return $this->fractal->item($schedule->refresh())
            ->transformWith(ScheduleTransformer::class)
            ->toArray();
    }

    /**
     * Executes a given schedule immediately rather than waiting on it's normally scheduled time
     * to pass. This does not care about the schedule state.
     *
     * @throws \Throwable
     */
    public function execute(TriggerScheduleRequest $request, Server $server, Schedule $schedule): Response
    {
        if (!$schedule->is_active) {
            throw new BadRequestHttpException('Cannot trigger schedule exception for a schedule that is not currently active.');
        }

        $this->service->handle($schedule, true);

        return $this->returnAccepted();
    }

    /**
     * Deletes a schedule and it's associated tasks.
     */
    public function delete(DeleteScheduleRequest $request, Server $server, Schedule $schedule): Response
    {
        $this->repository->delete($schedule->id);

        return $this->returnNoContent();
    }

    /**
     * Get the next run timestamp based on the cron data provided.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    protected function getNextRunAt(Request $request): Carbon
    {
        try {
            return Utilities::getScheduleNextRunDate(
                $request->input('minute'),
                $request->input('hour'),
                $request->input('day_of_month'),
                $request->input('month'),
                $request->input('day_of_week')
            );
        } catch (Exception $exception) {
            throw new DisplayException('The cron data provided does not evaluate to a valid expression.');
        }
    }
}
