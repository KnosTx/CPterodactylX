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

namespace Pterodactyl\Models;

use Auth;
use Cache;
use Carbon;
use Schema;
use Javascript;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Nicolaslopezj\Searchable\SearchableTrait;

class Server extends Model
{
    use Notifiable, SearchableTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'servers';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['daemonSecret', 'sftp_password'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'installed', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'node_id' => 'integer',
        'suspended' => 'integer',
        'owner_id' => 'integer',
        'memory' => 'integer',
        'swap' => 'integer',
        'disk' => 'integer',
        'io' => 'integer',
        'cpu' => 'integer',
        'oom_disabled' => 'integer',
        'allocation_id' => 'integer',
        'service_id' => 'integer',
        'option_id' => 'integer',
        'pack_id' => 'integer',
        'installed' => 'integer',
    ];

    /**
     * Parameters for search querying.
     *
     * @var array
     */
    protected $searchable = [
        'columns' => [
            'servers.name' => 10,
            'servers.username' => 10,
            'servers.uuidShort' => 9,
            'servers.uuid' => 8,
            'packs.name' => 7,
            'users.email' => 6,
            'users.username' => 6,
            'nodes.name' => 2,
        ],
        'joins' => [
            'packs' => ['packs.id', 'servers.pack_id'],
            'users' => ['users.id', 'servers.owner_id'],
            'nodes' => ['nodes.id', 'servers.node_id'],
        ],
    ];

    /**
     * Returns a single server specified by UUID.
     * DO NOT USE THIS TO MODIFY SERVER DETAILS OR SAVE THOSE DETAILS.
     * YOU WILL OVERWRITE THE SECRET KEY AND BREAK THINGS.
     *
     * @param  string  $uuid
     * @param  array   $with
     * @param  array   $withCount
     * @return \Pterodactyl\Models\Server
     * @todo   Remove $with and $withCount due to cache issues, they aren't used anyways.
     */
    public static function byUuid($uuid, array $with = [], array $withCount = [])
    {
        if (! Auth::check()) {
            throw new \Exception('You must call Server:byUuid as an authenticated user.');
        }

        // Results are cached because we call this functions a few times on page load.
        $result = Cache::tags(['Model:Server', 'Model:Server:byUuid:' . $uuid])->remember('Model:Server:byUuid:' . $uuid . Auth::user()->uuid, Carbon::now()->addMinutes(15), function () use ($uuid) {
            $query = self::with('service', 'node')->where(function ($q) use ($uuid) {
                $q->where('uuidShort', $uuid)->orWhere('uuid', $uuid);
            });

            if (! Auth::user()->isRootAdmin()) {
                $query->whereIn('id', Auth::user()->serverAccessArray());
            }

            return $query->first();
        });

        if (! is_null($result)) {
            $result->daemonSecret = Auth::user()->daemonToken($result);
        }

        return $result;
    }

    /**
     * Returns non-administrative headers for accessing a server on the daemon.
     *
     * @param  Pterodactyl\Models\User|null  $user
     * @return array
     */
    public function guzzleHeaders(User $user = null)
    {
        // If no specific user is passed, see if we can find an active
        // auth session to pull data from.
        if (is_null($user) && Auth::check()) {
            $user = Auth::user();
        }

        return [
            'X-Access-Server' => $this->uuid,
            'X-Access-Token' => ($user) ? $user->daemonToken($this) : $this->daemonSecret,
        ];
    }

    /**
     * Return an instance of the Guzzle client for this specific server using defined access token.
     *
     * @param  Pterodactyl\Models\User|null  $user
     * @return \GuzzleHttp\Client
     */
    public function guzzleClient(User $user = null)
    {
        return $this->node->guzzleClient($this->guzzleHeaders($user));
    }

    /**
     * Returns javascript object to be embedded on server view pages with relevant information.
     *
     * @param  array|null  $additional
     * @param  array|null  $overwrite
     * @return \Laracasts\Utilities\JavaScript\JavaScriptFacade
     */
    public function js($additional = null, $overwrite = null)
    {
        $response = [
            'server' => collect($this->makeVisible('daemonSecret'))->only([
                'uuid',
                'uuidShort',
                'daemonSecret',
                'username',
            ]),
            'node' => collect($this->node)->only([
                'fqdn',
                'scheme',
                'daemonListen',
            ]),
        ];

        if (is_array($additional)) {
            $response = array_merge($response, $additional);
        }

        if (is_array($overwrite)) {
            $response = $overwrite;
        }

        return Javascript::put($response);
    }

    /**
     * Return the columns available for this table.
     *
     * @return array
     */
    public function getTableColumns()
    {
        return Schema::getColumnListing($this->getTable());
    }

    /**
     * Gets the user who owns the server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Gets the subusers associated with a server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subusers()
    {
        return $this->hasMany(Subuser::class);
    }

    /**
     * Gets the default allocation for a server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function allocation()
    {
        return $this->hasOne(Allocation::class, 'id', 'allocation_id');
    }

    /**
     * Gets all allocations associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allocations()
    {
        return $this->hasMany(Allocation::class, 'server_id');
    }

    /**
     * Gets information for the pack associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pack()
    {
        return $this->belongsTo(Pack::class);
    }

    /**
     * Gets information for the service associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Gets information for the service option associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function option()
    {
        return $this->belongsTo(ServiceOption::class);
    }

    /**
     * Gets information for the service variables associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variables()
    {
        return $this->hasMany(ServerVariable::class);
    }

    /**
     * Gets information for the node associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Gets information for the tasks associated with this server.
     *
     * @TODO adjust server column in tasks to be server_id
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Gets all databases associated with a server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function databases()
    {
        return $this->hasMany(Database::class);
    }

    /**
     * Gets all downloads associated with a server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function downloads()
    {
        return $this->hasMany(Download::class, 'server', 'id');
    }

    /**
     * Gets the location of the server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function location()
    {
        return $this->node->location();
    }
}
