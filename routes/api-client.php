<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Api\Client;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Pterodactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client
|
*/
Route::get('/', 'ClientController@index')->name('api:client.index');
Route::get('/permissions', 'ClientController@permissions');

Route::group(['prefix' => '/account'], function () {
    Route::get('/', 'AccountController@index')->name('api:client.account')->withoutMiddleware(RequireTwoFactorAuthentication::class);
    Route::get('/two-factor', 'TwoFactorController@index')->withoutMiddleware(RequireTwoFactorAuthentication::class);
    Route::post('/two-factor', 'TwoFactorController@store')->withoutMiddleware(RequireTwoFactorAuthentication::class);
    Route::delete('/two-factor', 'TwoFactorController@delete')->withoutMiddleware(RequireTwoFactorAuthentication::class);

    Route::put('/email', 'AccountController@updateEmail')->name('api:client.account.update-email');
    Route::put('/password', 'AccountController@updatePassword')->name('api:client.account.update-password');

    Route::get('/api-keys', [Client\ApiKeyController::class, 'index']);
    Route::post('/api-keys', [Client\ApiKeyController::class, 'store']);
    Route::delete('/api-keys/{identifier}', [Client\ApiKeyController::class, 'delete']);

    Route::get('/webauthn', 'WebauthnController@index')->withoutMiddleware(RequireTwoFactorAuthentication::class);
    Route::get('/webauthn/register', 'WebauthnController@register')->withoutMiddleware(RequireTwoFactorAuthentication::class);
    Route::post('/webauthn/register', 'WebauthnController@create')->withoutMiddleware(RequireTwoFactorAuthentication::class);
    Route::delete('/webauthn/{id}', 'WebauthnController@deleteKey')->withoutMiddleware(RequireTwoFactorAuthentication::class);

    Route::get('/ssh', 'SSHKeyController@index');
    Route::post('/ssh', 'SSHKeyController@store');
    Route::delete('/ssh/{ssh_key}', 'SSHKeyController@delete');
});

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client/servers/{server}
|
*/
Route::group([
    'prefix' => '/servers/{server}',
    'middleware' => [AuthenticateServerAccess::class, ResourceBelongsToServer::class],
], function () {
    Route::get('/', 'Servers\ServerController@index')->name('api:client:server.view');
    Route::get('/websocket', 'Servers\WebsocketController')->name('api:client:server.ws');
    Route::get('/resources', 'Servers\ResourceUtilizationController')->name('api:client:server.resources');

    Route::post('/command', 'Servers\CommandController@index');
    Route::post('/power', 'Servers\PowerController@index');

    Route::group(['prefix' => '/databases'], function () {
        Route::get('/', 'Servers\DatabaseController@index');
        Route::post('/', 'Servers\DatabaseController@store');
        Route::post('/{database}/rotate-password', 'Servers\DatabaseController@rotatePassword');
        Route::delete('/{database}', 'Servers\DatabaseController@delete');
    });

    Route::group(['prefix' => '/files'], function () {
        Route::get('/list', [Client\Servers\FileController::class, 'directory']);
        Route::get('/contents', [Client\Servers\FileController::class, 'contents']);
        Route::get('/download', [Client\Servers\FileController::class, 'download']);
        Route::put('/rename', [Client\Servers\FileController::class, 'rename']);
        Route::post('/copy', [Client\Servers\FileController::class, 'copy']);
        Route::post('/write', [Client\Servers\FileController::class, 'write']);
        Route::post('/compress', [Client\Servers\FileController::class, 'compress']);
        Route::post('/decompress', [Client\Servers\FileController::class, 'decompress']);
        Route::post('/delete', [Client\Servers\FileController::class, 'delete']);
        Route::post('/create-folder', [Client\Servers\FileController::class, 'create']);
        Route::post('/chmod', [Client\Servers\FileController::class, 'chmod']);
        Route::post('/pull', [Client\Servers\FileController::class, 'pull'])->middleware(['throttle:pull']);
        Route::get('/upload', [Client\Servers\FileUploadController::class, '__invoke']);
    });

    Route::group(['prefix' => '/schedules'], function () {
        Route::get('/', 'Servers\ScheduleController@index');
        Route::post('/', 'Servers\ScheduleController@store');
        Route::get('/{schedule}', 'Servers\ScheduleController@view');
        Route::post('/{schedule}', 'Servers\ScheduleController@update');
        Route::post('/{schedule}/execute', 'Servers\ScheduleController@execute');
        Route::delete('/{schedule}', 'Servers\ScheduleController@delete');

        Route::post('/{schedule}/tasks', 'Servers\ScheduleTaskController@store');
        Route::post('/{schedule}/tasks/{task}', 'Servers\ScheduleTaskController@update');
        Route::delete('/{schedule}/tasks/{task}', 'Servers\ScheduleTaskController@delete');
    });

    Route::group(['prefix' => '/network'], function () {
        Route::get('/allocations', 'Servers\NetworkAllocationController@index');
        Route::post('/allocations', 'Servers\NetworkAllocationController@store');
        Route::post('/allocations/{allocation}', 'Servers\NetworkAllocationController@update');
        Route::post('/allocations/{allocation}/primary', 'Servers\NetworkAllocationController@setPrimary');
        Route::delete('/allocations/{allocation}', 'Servers\NetworkAllocationController@delete');
    });

    Route::group(['prefix' => '/users'], function () {
        Route::get('/', 'Servers\SubuserController@index');
        Route::post('/', 'Servers\SubuserController@store');
        Route::get('/{subuser}', [Client\Servers\SubuserController::class, 'view']);
        Route::post('/{subuser}', [Client\Servers\SubuserController::class, 'update']);
        Route::delete('/{subuser}', [Client\Servers\SubuserController::class, 'delete']);
    });

    Route::group(['prefix' => '/backups'], function () {
        Route::get('/', 'Servers\BackupController@index');
        Route::post('/', 'Servers\BackupController@store');
        Route::get('/{backup}', 'Servers\BackupController@view');
        Route::get('/{backup}/download', 'Servers\BackupController@download');
        Route::post('/{backup}/lock', 'Servers\BackupController@toggleLock');
        Route::post('/{backup}/restore', 'Servers\BackupController@restore');
        Route::delete('/{backup}', 'Servers\BackupController@delete');
    });

    Route::group(['prefix' => '/startup'], function () {
        Route::get('/', 'Servers\StartupController@index');
        Route::put('/variable', 'Servers\StartupController@update');
    });

    Route::group(['prefix' => '/settings'], function () {
        Route::post('/rename', 'Servers\SettingsController@rename');
        Route::post('/reinstall', 'Servers\SettingsController@reinstall');
        Route::put('/docker-image', 'Servers\SettingsController@dockerImage');
    });
});
