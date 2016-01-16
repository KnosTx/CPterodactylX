<?php

namespace Pterodactyl\Http\Routes;

use Illuminate\Routing\Router;

class AdminRoutes {

    public function map(Router $router) {

        // Admin Index
        $router->get('admin', [
            'as' => 'admin.index',
            'middleware' => [
                'auth',
                'admin',
                'csrf'
            ],
            'uses' => 'Admin\BaseController@getIndex'
        ]);

        $router->group([
            'prefix' => 'admin/accounts',
            'middleware' => [
                'auth',
                'admin',
                'csrf'
            ]
        ], function () use ($router) {

            // View All Accounts on System
            $router->get('/', [
                'as' => 'admin.accounts',
                'uses' => 'Admin\AccountsController@getIndex'
            ]);

            // View Specific Account
            $router->get('/view/{id}', [
                'as' => 'admin.accounts.view',
                'uses' => 'Admin\AccountsController@getView'
            ]);

            // Show Create Account Page
            $router->get('/new', [
                'as' => 'admin.accounts.new',
                'uses' => 'Admin\AccountsController@getNew'
            ]);

            // Handle Creating New Account
            $router->post('/new', [
                'uses' => 'Admin\AccountsController@postNew'
            ]);

            // Update A Specific Account
            $router->post('/update', [
                'uses' => 'Admin\AccountsController@postUpdate'
            ]);

            // Delete an Account Matching an ID
            $router->delete('/view/{id}', [
                'uses' => 'Admin\AccountsController@deleteView'
            ]);

        });

        // Server Routes
        $router->group([
            'prefix' => 'admin/servers',
            'middleware' => [
                'auth',
                'admin',
                'csrf'
            ]
        ], function () use ($router) {

            // View All Servers
            $router->get('/', [
                'as' => 'admin.servers',
                'uses' => 'Admin\ServersController@getIndex' ]);

            // View Create Server Page
            $router->get('/new', [
                'as' => 'admin.servers.new',
                'uses' => 'Admin\ServersController@getNew'
            ]);

            // Handle POST Request for Creating Server
            $router->post('/new', [
                'uses' => 'Admin\ServersController@postNewServer'
            ]);

            // Assorted Page Helpers
                $router->post('/new/get-nodes', [
                    'uses' => 'Admin\ServersController@postNewServerGetNodes'
                ]);

                $router->post('/new/get-ips', [
                    'uses' => 'Admin\ServersController@postNewServerGetIps'
                ]);

                $router->post('/new/service-options', [
                    'uses' => 'Admin\ServersController@postNewServerServiceOptions'
                ]);

                $router->post('/new/service-variables', [
                    'uses' => 'Admin\ServersController@postNewServerServiceVariables'
                ]);
            // End Assorted Page Helpers

            // View Specific Server
            $router->get('/view/{id}', [
                'as' => 'admin.servers.view',
                'uses' => 'Admin\ServersController@getView'
            ]);

            // Change Server Details
            $router->post('/view/{id}/details', [
                'uses' => 'Admin\ServersController@postUpdateServerDetails'
            ]);

            // Change Server Details
            $router->post('/view/{id}/startup', [
                'as' => 'admin.servers.post.startup',
                'uses' => 'Admin\ServersController@postUpdateServerStartup'
            ]);

            // Rebuild Server
            $router->post('/view/{id}/rebuild', [
                'uses' => 'Admin\ServersController@postUpdateServerToggleBuild'
            ]);

            // Change Build Details
            $router->post('/view/{id}/build', [
                'uses' => 'Admin\ServersController@postUpdateServerUpdateBuild'
            ]);

            // Change Install Status
            $router->post('/view/{id}/installed', [
                'uses' => 'Admin\ServersController@postToggleInstall'
            ]);

            // Delete [force delete]
            $router->delete('/view/{id}/{force?}', [
                'uses' => 'Admin\ServersController@deleteServer'
            ]);

        });

        // Node Routes
        $router->group([
            'prefix' => 'admin/nodes',
            'middleware' => [
                'auth',
                'admin',
                'csrf'
            ]
        ], function () use ($router) {

            // View All Nodes
            $router->get('/', [
                'as' => 'admin.nodes',
                'uses' => 'Admin\NodesController@getIndex'
            ]);

            // Add New Node
            $router->get('/new', [
                'as' => 'admin.nodes.new',
                'uses' => 'Admin\NodesController@getNew'
            ]);

            $router->post('/new', [
                'uses' => 'Admin\NodesController@postNew'
            ]);

            // View Node
            $router->get('/view/{id}', [
                'as' => 'admin.nodes.view',
                'uses' => 'Admin\NodesController@getView'
            ]);

            $router->post('/view/{id}', [
                'uses' => 'Admin\NodesController@postView'
            ]);

            $router->delete('/view/{id}/allocation/{ip}/{port?}', [
                'uses' => 'Admin\NodesController@deleteAllocation'
            ]);

            $router->get('/view/{id}/allocations.json', [
                'as' => 'admin.nodes.view.allocations',
                'uses' => 'Admin\NodesController@getAllocationsJson'
            ]);

            $router->post('/view/{id}/allocations', [
                'as' => 'admin.nodes.post.allocations',
                'uses' => 'Admin\NodesController@postAllocations'
            ]);

            $router->delete('/view/{id}', [
                'as' => 'admin.nodes.delete',
                'uses' => 'Admin\NodesController@deleteNode'
            ]);

        });

        // Server Routes
        $router->group([
            'prefix' => 'admin/locations',
            'middleware' => [
                'auth',
                'admin',
                'csrf'
            ]
        ], function () use ($router) {
            $router->get('/', [
                'as' => 'admin.locations',
                'uses' => 'Admin\LocationsController@getIndex'
            ]);
        });

    }

}
