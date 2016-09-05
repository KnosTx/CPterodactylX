<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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
namespace Pterodactyl\Http\Routes;

use Pterodactyl\Models;
use Illuminate\Routing\Router;

class APIRoutes
{

    public function map(Router $router) {

        $api = app('Dingo\Api\Routing\Router');
        $api->version('v1', ['middleware' => 'api.auth'], function ($api) {

            /**
             * User Routes
             */
            $api->get('users', [
                'as' => 'api.users.list',
                'uses' => 'Pterodactyl\Http\Controllers\API\UserController@list'
            ]);

            $api->post('users', [
                'as' => 'api.users.create',
                'uses' => 'Pterodactyl\Http\Controllers\API\UserController@create'
            ]);

            $api->get('users/{id}', [
                'as' => 'api.users.view',
                'uses' => 'Pterodactyl\Http\Controllers\API\UserController@view'
            ]);

            $api->patch('users/{id}', [
                'as' => 'api.users.update',
                'uses' => 'Pterodactyl\Http\Controllers\API\UserController@update'
            ]);

            $api->delete('users/{id}', [
                'as' => 'api.users.delete',
                'uses' => 'Pterodactyl\Http\Controllers\API\UserController@delete'
            ]);

            /**
             * Server Routes
             */
            $api->get('servers', [
                'as' => 'api.servers.list',
                'uses' => 'Pterodactyl\Http\Controllers\API\ServerController@list'
            ]);

            $api->post('servers', [
                'as' => 'api.servers.create',
                'uses' => 'Pterodactyl\Http\Controllers\API\ServerController@create'
            ]);

            $api->get('servers/{id}', [
                'as' => 'api.servers.view',
                'uses' => 'Pterodactyl\Http\Controllers\API\ServerController@view'
            ]);

            $api->patch('servers/{id}/config', [
                'as' => 'api.servers.config',
                'uses' => 'Pterodactyl\Http\Controllers\API\ServerController@config'
            ]);

            $api->patch('servers/{id}/build', [
                'as' => 'api.servers.build',
                'uses' => 'Pterodactyl\Http\Controllers\API\ServerController@build'
            ]);

            $api->post('servers/{id}/suspend', [
                'as' => 'api.servers.suspend',
                'uses' => 'Pterodactyl\Http\Controllers\API\ServerController@suspend'
            ]);

            $api->post('servers/{id}/unsuspend', [
                'as' => 'api.servers.unsuspend',
                'uses' => 'Pterodactyl\Http\Controllers\API\ServerController@unsuspend'
            ]);

            $api->delete('servers/{id}/{force?}', [
                'as' => 'api.servers.delete',
                'uses' => 'Pterodactyl\Http\Controllers\API\ServerController@delete'
            ]);

            /**
             * Node Routes
             */
            $api->get('nodes', [
                'as' => 'api.nodes.list',
                'uses' => 'Pterodactyl\Http\Controllers\API\NodeController@list'
            ]);

            $api->post('nodes', [
                'as' => 'api.nodes.create',
                'uses' => 'Pterodactyl\Http\Controllers\API\NodeController@create'
            ]);

            $api->get('nodes/allocations', [
                'as' => 'api.nodes.allocations',
                'uses' => 'Pterodactyl\Http\Controllers\API\NodeController@allocations'
            ]);

            $api->get('nodes/{id}', [
                'as' => 'api.nodes.view',
                'uses' => 'Pterodactyl\Http\Controllers\API\NodeController@view'
            ]);

            $api->delete('nodes/{id}', [
                'as' => 'api.nodes.delete',
                'uses' => 'Pterodactyl\Http\Controllers\API\NodeController@delete'
            ]);

            /**
             * Location Routes
             */
            $api->get('locations', [
                'as' => 'api.locations.list',
                'uses' => 'Pterodactyl\Http\Controllers\API\LocationController@list'
            ]);

            /**
             * Service Routes
             */
            $api->get('services', [
                'as' => 'api.services.list',
                'uses' => 'Pterodactyl\Http\Controllers\API\ServiceController@list'
            ]);

            $api->get('services/{id}', [
                'as' => 'api.services.view',
                'uses' => 'Pterodactyl\Http\Controllers\API\ServiceController@view'
            ]);

        });
    }

}
