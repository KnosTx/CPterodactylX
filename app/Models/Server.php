<?php

namespace Pterodactyl\Models;

use Auth;
use Validator;
use Debugbar;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\AccountNotFoundException;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Permission;
use Pterodactyl\Models\Subuser;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{

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
    protected $hidden = ['daemonSecret'];

    /**
     * @var array
     */
    protected static $serverUUIDInstance = [];

    /**
     * @var mixed
     */
    protected static $user;

    /**
     * Constructor
     */
    public function __construct()
    {
        self::$user = Auth::user();
    }

    /**
     * Determine if we need to change the server's daemonSecret value to
     * match that of the user if they are a subuser.
     *
     * @param Illuminate\Database\Eloquent\Model\Server $server
     * @return string
     */
    protected static function getUserDaemonSecret(Server $server)
    {

        if (self::$user->id === $server->owner || self::$user->root_admin === 1) {
            return $server->daemonSecret;
        }

        $subuser = Subuser::where('server_id', $server->id)->where('user_id', self::$user->id)->first();

        if (is_null($subuser)) {
            return null;
        }

        return $subuser->daemonSecret;

    }

    /**
     * Returns array of all servers owned by the logged in user.
     * Returns all active servers if user is a root admin.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserServers()
    {

        $query = self::select('servers.*', 'nodes.name as nodeName', 'locations.long as location')
                    ->join('nodes', 'servers.node', '=', 'nodes.id')
                    ->join('locations', 'nodes.location', '=', 'locations.id')
                    ->where('active', 1);

        if (self::$user->root_admin !== 1) {
            $query->whereIn('servers.id', Subuser::accessServers());
        }

        return $query->get();

    }

    /**
     * Returns a single server specified by UUID
     *
     * @param  string $uuid The Short-UUID of the server to return an object about.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByUUID($uuid)
    {

        if (array_key_exists($uuid, self::$serverUUIDInstance)) {
            return self::$serverUUIDInstance[$uuid];
        }

        $query = self::where('uuidShort', $uuid)->where('active', 1);

        if (self::$user->root_admin !== 1) {
            $query->whereIn('servers.id', Subuser::accessServers());
        }

        $result = $query->first();

        if(!is_null($result)) {
            $result->daemonSecret = self::getUserDaemonSecret($result);
        }

        self::$serverUUIDInstance[$uuid] = $result;
        return self::$serverUUIDInstance[$uuid];

    }

    /**
     * Returns non-administrative headers for accessing a server on Scales
     *
     * @param  string $uuid
     * @return array
     */
    public static function getGuzzleHeaders($uuid)
    {

        if (array_key_exists($uuid, self::$serverUUIDInstance)) {
            return [
                'X-Access-Server' => self::$serverUUIDInstance[$uuid]->uuid,
                'X-Access-Token' => self::$serverUUIDInstance[$uuid]->daemonSecret
            ];
        }

        return [];

    }

    /**
     * Adds a new server to the system.
     * @param array  $data  An array of data descriptors for creating the server. These should align to the columns in the database.
     */
    public static function addServer(array $data)
    {

        // Validate Fields
        $validator = Validator::make($data, [
            'owner' => 'required|email|exists:users,email',
            'node' => 'required|numeric|min:1',
            'name' => 'required|regex:([\w -]{4,35})',
            'memory' => 'required|numeric|min:1',
            'disk' => 'required|numeric|min:1',
            'cpu' => 'required|numeric|min:0',
            'io' => 'required|numeric|min:10|max:1000',
            'ip' => 'required|ip',
            'port' => 'required|numeric|min:1|max:65535',
            'service' => 'required|numeric|min:1|exists:services,id',
            'option' => 'required|numeric|min:1|exists:service_options,id',
            'custom_image_name' => 'required_if:use_custom_image,on',
        ]);

        // @TODO: Have this return a JSON response.
        if ($validator->fails()) {
            foreach($validator->errors()->all() as $error) {
                Debugbar::info($error);
            }
        }

        return;

    }

}
