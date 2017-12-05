<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Transformers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use League\Fractal\TransformerAbstract;

class ServerTransformer extends TransformerAbstract
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = [
        'allocations',
        'user',
        'subusers',
        'pack',
        'service',
        'option',
        'variables',
        'location',
        'node',
    ];

    /**
     * The Illuminate Request object if provided.
     *
     * @var \Illuminate\Http\Request|bool
     */
    protected $request;

    /**
     * Setup request object for transformer.
     *
     * @param \Illuminate\Http\Request|bool $request
     */
    public function __construct($request = false)
    {
        if (! $request instanceof Request && $request !== false) {
            throw new DisplayException('Request passed to constructor must be of type Request or false.');
        }

        $this->request = $request;
    }

    /**
     * Return a generic transformed server array.
     *
     * @param Server $server
     * @return array
     */
    public function transform(Server $server)
    {
        return collect($server->toArray())->only($server->getTableColumns())->toArray();
    }

    /**
     * Return a generic array of allocations for this server.
     *
     * @param Server $server
     * @return \League\Fractal\Resource\Collection
     */
    public function includeAllocations(Server $server)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('server-view')) {
            return;
        }

        return $this->collection($server->allocations, new AllocationTransformer($this->request, 'server'), 'allocation');
    }

    /**
     * Return a generic array of data about subusers for this server.
     *
     * @param Server $server
     * @return \League\Fractal\Resource\Collection
     */
    public function includeSubusers(Server $server)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('server-view')) {
            return;
        }

        return $this->collection($server->subusers, new SubuserTransformer($this->request), 'subuser');
    }

    /**
     * Return a generic array of data about subusers for this server.
     *
     * @param Server $server
     * @return \League\Fractal\Resource\Item
     */
    public function includeUser(Server $server)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('user-view')) {
            return;
        }

        return $this->item($server->user, new UserTransformer($this->request), 'user');
    }

    /**
     * Return a generic array with pack information for this server.
     *
     * @param Server $server
     * @return \League\Fractal\Resource\Item
     */
    public function includePack(Server $server)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('pack-view')) {
            return;
        }

        return $this->item($server->pack, new PackTransformer($this->request), 'pack');
    }

    /**
     * Return a generic array with service information for this server.
     *
     * @param Server $server
     * @return \League\Fractal\Resource\Item
     */
    public function includeService(Server $server)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('service-view')) {
            return;
        }

        return $this->item($server->service, new ServiceTransformer($this->request), 'service');
    }

    /**
     * Return a generic array with service option information for this server.
     *
     * @param Server $server
     * @return \League\Fractal\Resource\Item
     */
    public function includeOption(Server $server)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('option-view')) {
            return;
        }

        return $this->item($server->option, new OptionTransformer($this->request), 'option');
    }

    /**
     * Return a generic array of data about subusers for this server.
     *
     * @param Server $server
     * @return \League\Fractal\Resource\Collection
     */
    public function includeVariables(Server $server)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('server-view')) {
            return;
        }

        return $this->collection($server->variables, new ServerVariableTransformer($this->request), 'server_variable');
    }

    /**
     * Return a generic array with pack information for this server.
     *
     * @param Server $server
     * @return \League\Fractal\Resource\Item
     */
    public function includeLocation(Server $server)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('location-list')) {
            return;
        }

        return $this->item($server->location, new LocationTransformer($this->request), 'location');
    }

    /**
     * Return a generic array with pack information for this server.
     *
     * @param Server $server
     * @return \League\Fractal\Resource\Item|void
     */
    public function includeNode(Server $server)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('node-view')) {
            return;
        }

        return $this->item($server->node, new NodeTransformer($this->request), 'node');
    }
}
