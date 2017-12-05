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
use Pterodactyl\Models\EggVariable;
use League\Fractal\TransformerAbstract;

class ServiceVariableTransformer extends TransformerAbstract
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = ['variables'];

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
     * Return a generic transformed server variable array.
     *
     * @param EggVariable $variable
     * @return array
     */
    public function transform(EggVariable $variable)
    {
        return $variable->toArray();
    }

    /**
     * Return the server variables associated with this variable.
     *
     * @param EggVariable $variable
     * @return \League\Fractal\Resource\Collection
     */
    public function includeVariables(EggVariable $variable)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('server-view')) {
            return;
        }

        return $this->collection($variable->serverVariable, new ServerVariableTransformer($this->request), 'server_variable');
    }
}
