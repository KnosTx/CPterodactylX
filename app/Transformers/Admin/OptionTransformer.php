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

namespace Pterodactyl\Transformers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Models\ServiceOption;
use League\Fractal\TransformerAbstract;

class OptionTransformer extends TransformerAbstract
{
    /**
     * List of resources that can be included.
     *
     * @var array
     */
    protected $availableIncludes = [
        'service',
        'packs',
        'servers',
        'variables',
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
     * @param  \Illuminate\Http\Request|bool  $request
     * @return void
     */
    public function __construct($request = false)
    {
        if (! $request instanceof Request && $request !== false) {
            throw new DisplayException('Request passed to constructor must be of type Request or false.');
        }

        $this->request = $request;
    }

    /**
     * Return a generic transformed service option array.
     *
     * @return array
     */
    public function transform(ServiceOption $option)
    {
        return $option->toArray();
    }

    /**
     * Return the parent service for this service option.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeService(ServiceOption $option)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('service-view')) {
            return;
        }

        return $this->item($option->service, new ServiceTransformer($this->request), 'service');
    }

    /**
     * Return the packs associated with this service option.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includePacks(ServiceOption $option)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('pack-list')) {
            return;
        }

        return $this->collection($option->packs, new PackTransformer($this->request), 'pack');
    }

    /**
     * Return the servers associated with this service option.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeServers(ServiceOption $option)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('server-list')) {
            return;
        }

        return $this->collection($option->servers, new ServerTransformer($this->request), 'server');
    }

    /**
     * Return the variables for this service option.
     *
     * @return \Leauge\Fractal\Resource\Collection
     */
    public function includeVariables(ServiceOption $option)
    {
        if ($this->request && ! $this->request->apiKeyHasPermission('option-view')) {
            return;
        }

        return $this->collection($option->variables, new ServiceVariableTransformer($this->request), 'variable');
    }
}
