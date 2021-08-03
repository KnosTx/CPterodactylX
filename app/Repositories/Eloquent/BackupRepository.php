<?php

namespace Pterodactyl\Repositories\Eloquent;

use Carbon\Carbon;
use Pterodactyl\Models\Backup;

class BackupRepository extends EloquentRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Backup::class;
    }

    /**
     * Determines if too many backups have been generated by the server.
     *
     * @return \Pterodactyl\Models\Backup[]|\Illuminate\Support\Collection
     */
    public function getBackupsGeneratedDuringTimespan(int $server, int $seconds = 600)
    {
        return $this->getBuilder()
            ->withTrashed()
            ->where('server_id', $server)
            ->whereNull('completed_at')
            ->orWhere('is_successful', '=', true)
            ->where('created_at', '>=', Carbon::now()->subSeconds($seconds)->toDateTimeString())
            ->get()
            ->toBase();
    }
}
