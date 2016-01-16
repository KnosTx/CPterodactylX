<?php

namespace Pterodactyl\Repositories;

use DB;
use Debugbar;
use Validator;
use Log;

use Pterodactyl\Models;
use Pterodactyl\Services\UuidService;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\AccountNotFoundException;
use Pterodactyl\Exceptions\DisplayValidationException;

class ServerRepository
{

    public function __construct()
    {
        //
    }

    /**
     * Generates a SFTP username for a server given a server name.
     *
     * @param  string $name
     * @return string
     */
    protected function generateSFTPUsername($name)
    {

        $name = preg_replace('/\s+/', '', $name);
        if (strlen($name) > 6) {
            return strtolower('ptdl-' . substr($name, 0, 6) . '_' . str_random(5));
        }

        return strtolower('ptdl-' . $name . '_' . str_random((11 - strlen($name))));

    }

    /**
     * Adds a new server to the system.
     * @param   array  $data  An array of data descriptors for creating the server. These should align to the columns in the database.
     * @return  integer
     */
    public function create(array $data)
    {

        // Validate Fields
        $validator = Validator::make($data, [
            'owner' => 'required|email|exists:users,email',
            'node' => 'required|numeric|min:1|exists:nodes,id',
            'name' => 'required|regex:/^([\w -]{4,35})$/',
            'memory' => 'required|numeric|min:1',
            'swap' => 'required|numeric|min:0',
            'disk' => 'required|numeric|min:1',
            'cpu' => 'required|numeric|min:0',
            'io' => 'required|numeric|min:10|max:1000',
            'ip' => 'required|ip',
            'port' => 'required|numeric|min:1|max:65535',
            'service' => 'required|numeric|min:1|exists:services,id',
            'option' => 'required|numeric|min:1|exists:service_options,id',
            'startup' => 'required',
            'custom_image_name' => 'required_if:use_custom_image,on',
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        // Get the User ID; user exists since we passed the 'exists:users,email' part of the validation
        $user = Models\User::select('id')->where('email', $data['owner'])->first();

        // Verify IP & Port are a.) free and b.) assigned to the node.
        // We know the node exists because of 'exists:nodes,id' in the validation
        $node = Models\Node::getByID($data['node']);
        $allocation = Models\Allocation::where('ip', $data['ip'])->where('port', $data['port'])->where('node', $data['node'])->whereNull('assigned_to')->first();

        // Something failed in the query, either that combo doesn't exist, or it is in use.
        if (!$allocation) {
            throw new DisplayException('The selected IP/Port combination (' . $data['ip'] . ':' . $data['port'] . ') is either already in use, or unavaliable for this node.');
        }

        // Validate those Service Option Variables
        // We know the service and option exists because of the validation.
        // We need to verify that the option exists for the service, and then check for
        // any required variable fields. (fields are labeled env_<env_variable>)
        $option = Models\ServiceOptions::where('id', $data['option'])->where('parent_service', $data['service'])->first();
        if (!$option) {
            throw new DisplayException('The requested service option does not exist for the specified service.');
        }

        // Load up the Service Information
        $service = Models\Service::find($option->parent_service);

        // Check those Variables
        $variables = Models\ServiceVariables::where('option_id', $data['option'])->get();
        $variableList = [];
        if ($variables) {
            foreach($variables as $variable) {

                // Is the variable required?
                if (!$data['env_' . $variable->env_variable]) {
                    if ($variable->required === 1) {
                        throw new DisplayException('A required service option variable field (env_' . $variable->env_variable . ') was missing from the request.');
                    }
                    $variableList = array_merge($variableList, [[
                        'id' => $variable->id,
                        'env' => $variable->env_variable,
                        'val' => $variable->default_value
                    ]]);
                    continue;
                }

                // Check aganist Regex Pattern
                if (!is_null($variable->regex) && !preg_match($variable->regex, $data['env_' . $variable->env_variable])) {
                    throw new DisplayException('Failed to validate service option variable field (env_' . $variable->env_variable . ') aganist regex (' . $variable->regex . ').');
                }

                $variableList = array_merge($variableList, [[
                    'id' => $variable->id,
                    'env' => $variable->env_variable,
                    'val' => $data['env_' . $variable->env_variable]
                ]]);
                continue;
            }
        }

        // Check Overallocation
        if (is_numeric($node->memory_overallocate) || is_numeric($node->disk_overallocate)) {

            $totals = Models\Server::select(DB::raw('SUM(memory) as memory, SUM(disk) as disk'))->where('node', $node->id)->first();

            // Check memory limits
            if (is_numeric($node->memory_overallocate)) {
                $newMemory = $totals->memory + $data['memory'];
                $memoryLimit = ($node->memory * (1 + ($node->memory_overallocate / 100)));
                if($newMemory > $memoryLimit) {
                    throw new DisplayException('The amount of memory allocated to this server would put the node over its allocation limits. This node is allowed ' . ($node->memory_overallocate + 100) . '% of its assigned ' . $node->memory . 'Mb of memory (' . $memoryLimit . 'Mb) of which ' . (($totals->memory / $node->memory) * 100) . '% (' . $totals->memory . 'Mb) is in use already. By allocating this server the node would be at ' . (($newMemory / $node->memory) * 100) . '% (' . $newMemory . 'Mb) usage.');
                }
            }

            // Check Disk Limits
            if (is_numeric($node->disk_overallocate)) {
                $newDisk = $totals->disk + $data['disk'];
                $diskLimit = ($node->disk * (1 + ($node->disk_overallocate / 100)));
                if($newDisk > $diskLimit) {
                    throw new DisplayException('The amount of disk allocated to this server would put the node over its allocation limits. This node is allowed ' . ($node->disk_overallocate + 100) . '% of its assigned ' . $node->disk . 'Mb of disk (' . $diskLimit . 'Mb) of which ' . (($totals->disk / $node->disk) * 100) . '% (' . $totals->disk . 'Mb) is in use already. By allocating this server the node would be at ' . (($newDisk / $node->disk) * 100) . '% (' . $newDisk . 'Mb) usage.');
                }
            }

        }

        DB::beginTransaction();

        $uuid = new UuidService;

        // Add Server to the Database
        $server = new Models\Server;
        $generatedUuid = $uuid->generate('servers', 'uuid');
        $server->fill([
            'uuid' => $generatedUuid,
            'uuidShort' => $uuid->generateShort('servers', 'uuidShort', $generatedUuid),
            'node' => $data['node'],
            'name' => $data['name'],
            'active' => 1,
            'owner' => $user->id,
            'memory' => $data['memory'],
            'swap' => $data['swap'],
            'disk' => $data['disk'],
            'io' => $data['io'],
            'cpu' => $data['cpu'],
            'oom_disabled' => (isset($data['oom_disabled'])) ? true : false,
            'ip' => $data['ip'],
            'port' => $data['port'],
            'service' => $data['service'],
            'option' => $data['option'],
            'startup' => $data['startup'],
            'daemonSecret' => $uuid->generate('servers', 'daemonSecret'),
            'username' => $this->generateSFTPUsername($data['name'])
        ]);
        $server->save();

        // Mark Allocation in Use
        $allocation->assigned_to = $server->id;
        $allocation->save();

        // Add Variables
        $environmentVariables = [];
        $environmentVariables = array_merge($environmentVariables, [
            'STARTUP' => $data['startup']
        ]);
        foreach($variableList as $item) {
            $environmentVariables = array_merge($environmentVariables, [
                $item['env'] => $item['val']
            ]);
            Models\ServerVariables::create([
                'server_id' => $server->id,
                'variable_id' => $item['id'],
                'variable_value' => $item['val']
            ]);
        }

        try {

            $client = Models\Node::guzzleRequest($node->id);
            $client->request('POST', '/servers', [
                'headers' => [
                    'X-Access-Token' => $node->daemonSecret
                ],
                'json' => [
                    'uuid' => (string) $server->uuid,
                    'user' => $server->username,
                    'build' => [
                        'default' => [
                            'ip' => $server->ip,
                            'port' => (int) $server->port
                        ],
                        'ports' => [
                            (string) $server->ip => [ (int) $server->port ]
                        ],
                        'env' => $environmentVariables,
                        'memory' => (int) $server->memory,
                        'swap' => (int) $server->swap,
                        'io' => (int) $server->io,
                        'cpu' => (int) $server->cpu,
                        'disk' => (int) $server->disk,
                        'image' => (isset($data['custom_image_name'])) ? $data['custom_image_name'] : $option->docker_image
                    ],
                    'service' => [
                        'type' => $service->file,
                        'option' => $option->tag
                    ],
                    'keys' => [
                        (string) $server->daemonSecret => [
                            's:get',
                            's:power',
                            's:console',
                            's:command',
                            's:files:get',
                            's:files:read',
                            's:files:post',
                            's:files:delete',
                            's:files:upload',
                            's:set-password'
                        ]
                    ],
                    'rebuild' => false
                ]
            ]);

            DB::commit();
            return $server->id;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('An error occured while attempting to create the server: ' . $ex->getMessage());
        } catch (\Exception $ex) {
            DB::rollBack();
            Log:error($ex);
            throw $ex;
        }

    }

    /**
     * [updateDetails description]
     * @param  integer  $id
     * @param  array    $data
     * @return boolean
     */
    public function updateDetails($id, array $data)
    {

        $uuid = new UuidService;
        $resetDaemonKey = false;

        // Validate Fields
        $validator = Validator::make($data, [
            'owner' => 'email|exists:users,email',
            'name' => 'regex:([\w -]{4,35})'
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::beginTransaction();
        $server = Models\Server::findOrFail($id);
        $owner = Models\User::findOrFail($server->owner);

        // Update daemon secret if it was passed.
        if ((isset($data['reset_token']) && $data['reset_token'] === true) || (isset($data['owner']) && $data['owner'] !== $owner->email)) {
            $oldDaemonKey = $server->daemonSecret;
            $server->daemonSecret = $uuid->generate('servers', 'daemonSecret');
            $resetDaemonKey = true;
        }

        // Update Server Owner if it was passed.
        if (isset($data['owner']) && $data['owner'] !== $owner->email) {
            $newOwner = Models\User::select('id')->where('email', $data['owner'])->first();
            $server->owner = $newOwner->id;
        }

        // Update Server Name if it was passed.
        if (isset($data['name'])) {
            $server->name = $data['name'];
        }

        // Save our changes
        $server->save();

        // Do we need to update? If not, return successful.
        if (!$resetDaemonKey) {
            DB::commit();
            return true;
        }

        // If we need to update do it here.
        try {

            $node = Models\Node::getByID($server->node);
            $client = Models\Node::guzzleRequest($server->node);

            $res = $client->request('PATCH', '/server', [
                'headers' => [
                    'X-Access-Server' => $server->uuid,
                    'X-Access-Token' => $node->daemonSecret
                ],
                'exceptions' => false,
                'json' => [
                    'keys' => [
                        (string) $oldDaemonKey => [],
                        (string) $server->daemonSecret => [
                            's:get',
                            's:power',
                            's:console',
                            's:command',
                            's:files:get',
                            's:files:read',
                            's:files:post',
                            's:files:delete',
                            's:files:upload',
                            's:set-password'
                        ]
                    ]
                ]
            ]);

            if ($res->getStatusCode() === 204) {
                DB::commit();
                return true;
            } else {
                throw new DisplayException('Daemon returned a a non HTTP/204 error code. HTTP/' + $res->getStatusCode());
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex);
            throw new DisplayException('An error occured while attempting to update this server\'s information.');
        }

    }

    /**
     * [changeBuild description]
     * @param  integer  $id
     * @param  array    $data
     * @return boolean
     */
    public function changeBuild($id, array $data)
    {

        $validator = Validator::make($data, [
            'default' => [
                'string',
                'regex:/^(\d|[1-9]\d|1\d\d|2([0-4]\d|5[0-5]))\.(\d|[1-9]\d|1\d\d|2([0-4]\d|5[0-5]))\.(\d|[1-9]\d|1\d\d|2([0-4]\d|5[0-5]))\.(\d|[1-9]\d|1\d\d|2([0-4]\d|5[0-5])):(\d{1,5})$/'
            ],
            'add_additional' => 'array',
            'remove_additional' => 'array',
            'memory' => 'integer|min:0',
            'swap' => 'integer|min:0',
            'io' => 'integer|min:10|max:1000',
            'cpu' => 'integer|min:0',
            'disk' => 'integer|min:0'
        ]);

        // Run validator, throw catchable and displayable exception if it fails.
        // Exception includes a JSON result of failed validation rules.
        if ($validator->fails()) {
            throw new DisplayValidationException($validator->errors());
        }

        DB::beginTransaction();
        $server = Models\Server::findOrFail($id);

        if (isset($data['default'])) {
            list($ip, $port) = explode(':', $data['default']);
            if ($ip !== $server->ip || $port !== $server->port) {
                $allocation = Models\Allocation::where('ip', $ip)->where('port', $port)->where('assigned_to', $server->id)->get();
                if (!$allocation) {
                    throw new DisplayException('The assigned default connection (' . $ip . ':' . $prot . ') is not allocated to this server.');
                }

                $server->ip = $ip;
                $server->port = $port;
            }
        }

        // Remove Assignments
        if (isset($data['remove_additional'])) {
            foreach ($data['remove_additional'] as $id => $combo) {
                list($ip, $port) = explode(':', $combo);
                // Invalid, not worth killing the whole thing, we'll just skip over it.
                if (!filter_var($ip, FILTER_VALIDATE_IP) || !preg_match('/^(\d{1,5})$/', $port)) {
                    continue;
                }

                // Can't remove the assigned IP/Port combo
                if ($ip === $server->ip && $port === $server->port) {
                    continue;
                }

                Models\Allocation::where('ip', $ip)->where('port', $port)->where('assigned_to', $server->id)->update([
                    'assigned_to' => null
                ]);
            }
        }

        // Add Assignments
        if (isset($data['add_additional'])) {
            foreach ($data['add_additional'] as $id => $combo) {
                list($ip, $port) = explode(':', $combo);
                // Invalid, not worth killing the whole thing, we'll just skip over it.
                if (!filter_var($ip, FILTER_VALIDATE_IP) || !preg_match('/^(\d{1,5})$/', $port)) {
                    continue;
                }

                // Don't allow double port assignments
                if (Models\Allocation::where('port', $port)->where('assigned_to', $server->id)->count() !== 0) {
                    continue;
                }

                Models\Allocation::where('ip', $ip)->where('port', $port)->whereNull('assigned_to')->update([
                    'assigned_to' => $server->id
                ]);
            }
        }

        // Loop All Assignments
        $additionalAssignments = [];
        $assignments = Models\Allocation::where('assigned_to', $server->id)->get();
        foreach ($assignments as &$assignment) {
            if (array_key_exists((string) $assignment->ip, $additionalAssignments)) {
                array_push($additionalAssignments[ (string) $assignment->ip ], (int) $assignment->port);
            } else {
                $additionalAssignments[ (string) $assignment->ip ] = [ (int) $assignment->port ];
            }
        }

        // @TODO: verify that server can be set to this much memory without
        // going over node limits.
        if (isset($data['memory'])) {
            $server->memory = $data['memory'];
        }

        if (isset($data['swap'])) {
            $server->swap = $data['swap'];
        }

        // @TODO: verify that server can be set to this much disk without
        // going over node limits.
        if (isset($data['disk'])) {
            $server->disk = $data['disk'];
        }

        if (isset($data['cpu'])) {
            $server->cpu = $data['cpu'];
        }

        if (isset($data['io'])) {
            $server->io = $data['io'];
        }

        try {

            $node = Models\Node::getByID($server->node);
            $client = Models\Node::guzzleRequest($server->node);

            $client->request('PATCH', '/server', [
                'headers' => [
                    'X-Access-Server' => $server->uuid,
                    'X-Access-Token' => $node->daemonSecret
                ],
                'json' => [
                    'build' => [
                        'default' => [
                            'ip' => $server->ip,
                            'port' => (int) $server->port
                        ],
                        'ports|overwrite' => $additionalAssignments,
                        'memory' => (int) $server->memory,
                        'swap' => (int) $server->swap,
                        'io' => (int) $server->io,
                        'cpu' => (int) $server->cpu,
                        'disk' => (int) $server->disk
                    ]
                ]
            ]);

            $server->save();
            DB::commit();
            return true;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('An error occured while attempting to update the configuration: ' . $ex->getMessage());
        }

    }

    public function updateStartup($id, array $data)
    {

        $server = Models\Server::findOrFail($id);

        DB::beginTransaction();

        // Check the startup
        if (isset($data['startup'])) {
            $server->startup = $data['startup'];
            $server->save();
        }

        // Check those Variables
        $variables = Models\ServiceVariables::select('service_variables.*', 'server_variables.variable_value as a_currentValue')
            ->join('server_variables', 'server_variables.variable_id', '=', 'service_variables.id')
            ->where('option_id', $server->option)->get();

        $variableList = [];
        if ($variables) {
            foreach($variables as &$variable) {
                // Move on if the new data wasn't even sent
                if (!isset($data[$variable->env_variable])) {
                    $variableList = array_merge($variableList, [[
                        'id' => $variable->id,
                        'env' => $variable->env_variable,
                        'val' => $variable->a_currentValue
                    ]]);
                    continue;
                }

                // Update Empty but skip validation
                if (empty($data[$variable->env_variable])) {
                    $variableList = array_merge($variableList, [[
                        'id' => $variable->id,
                        'env' => $variable->env_variable,
                        'val' => null
                    ]]);
                    continue;
                }

                // Is the variable required?
                // @TODO: is this even logical to perform this check?
                if (isset($data[$variable->env_variable]) && empty($data[$variable->env_variable])) {
                    if ($variable->required === 1) {
                        throw new DisplayException('A required service option variable field (' . $variable->env_variable . ') was included in this request but was left blank.');
                    }
                }

                // Check aganist Regex Pattern
                if (!is_null($variable->regex) && !preg_match($variable->regex, $data[$variable->env_variable])) {
                    throw new DisplayException('Failed to validate service option variable field (' . $variable->env_variable . ') aganist regex (' . $variable->regex . ').');
                }

                $variableList = array_merge($variableList, [[
                    'id' => $variable->id,
                    'env' => $variable->env_variable,
                    'val' => $data[$variable->env_variable]
                ]]);
            }
        }

        // Add Variables
        $environmentVariables = [];
        $environmentVariables = array_merge($environmentVariables, [
            'STARTUP' => $server->startup
        ]);
        foreach($variableList as $item) {
            $environmentVariables = array_merge($environmentVariables, [
                $item['env'] => $item['val']
            ]);
            $var = Models\ServerVariables::where('server_id', $server->id)->where('variable_id', $item['id'])->update([
                'variable_value' => $item['val']
            ]);
        }

        try {

            $node = Models\Node::getByID($server->node);
            $client = Models\Node::guzzleRequest($server->node);

            $client->request('PATCH', '/server', [
                'headers' => [
                    'X-Access-Server' => $server->uuid,
                    'X-Access-Token' => $node->daemonSecret
                ],
                'json' => [
                    'build' => [
                        'env|overwrite' => $environmentVariables
                    ]
                ]
            ]);

            DB::commit();
            return true;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            DB::rollBack();
            throw new DisplayException('An error occured while attempting to update the server configuration: ' . $ex->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    }

    public function deleteServer($id, $force)
    {
        $server = Models\Server::findOrFail($id);
        $node = Models\Node::findOrFail($server->node);
        DB::beginTransaction();

        // Delete Allocations
        Models\Allocation::where('assigned_to', $server->id)->update([
            'assigned_to' => null
        ]);

        // Remove Variables
        Models\ServerVariables::where('server_id', $server->id)->delete();

        // Remove SubUsers
        Models\Subuser::where('server_id', $server->id)->delete();

        // Remove Permissions
        Models\Permission::where('server_id', $server->id)->delete();

        // Remove Downloads
        Models\Download::where('server', $server->uuid)->delete();

        try {
            $client = Models\Node::guzzleRequest($server->node);
            $client->request('DELETE', '/servers', [
                'headers' => [
                    'X-Access-Token' => $node->daemonSecret,
                    'X-Access-Server' => $server->uuid
                ]
            ]);

            $server->delete();
            DB::commit();
            return true;
        } catch (\GuzzleHttp\Exception\TransferException $ex) {
            if ($force === 'force') {
                $server->delete();
                DB::commit();
                return true;
            } else {
                DB::rollBack();
                Log::error($ex);
                throw new DisplayException('An error occured while attempting to delete the server on the daemon: ' . $ex->getMessage());
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function toggleInstall($id)
    {
        $server = Models\Server::findOrFail($id);
        $server->installed = ($server->installed === 1) ? 0 : 1;
        return $server->save();
    }

    /**
     * Suspends a server instance making it unable to be booted or used by a user.
     * @param  integer $id
     * @return boolean
     */
    public function suspend($id)
    {
        // @TODO: Implement logic; not doing it now since that is outside of the
        // scope of this API brance.
        return true;
    }

    /**
     * Unsuspends a server instance.
     * @param  integer $id
     * @return boolean
     */
    public function unsuspend($id)
    {
        // @TODO: Implement logic; not doing it now since that is outside of the
        // scope of this API brance.
        return true;
    }

}
