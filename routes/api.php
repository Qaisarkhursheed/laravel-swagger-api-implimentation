<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HostController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ServiceGroupController;
use App\Http\Controllers\Api\HostGroupController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CommandController;
use App\Http\Controllers\Api\StatusController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/




    Route::middleware('auth.op5')->group(function () {
        Route::get('/config/host/{identifier?}', [HostController::class, 'getAllHosts']);
        Route::post('/config/host', [HostController::class, 'createHost']);
        Route::put('/config/host/{host}', [HostController::class, 'updateHost']);
        Route::patch('/config/host/{hostName}', [HostController::class, 'patchHost']);
        Route::delete('/config/host/{id}', [HostController::class, 'deleteHost']);


        Route::get('/config/service/{hostName?}/{serviceDescription?}', [ServiceController::class, 'getAllServices']);
        Route::post('/config/service', [ServiceController::class, 'createService']);

        Route::put('/config/service/{service}', [ServiceController::class, 'updateService']);
        Route::patch('/config/service/{service}', [ServiceController::class, 'patchService']);
        Route::delete('/config/service/{id}', [ServiceController::class, 'deleteService']);

        //Routes for Service Groups
        Route::get('/config/servicegroup/{identifier?}', [ServiceGroupController::class, 'getAllServiceGroups']);
        Route::post('/config/servicegroup', [ServiceGroupController::class, 'createServiceGroups']);
        Route::put('/config/servicegroup/{params}', [ServiceGroupController::class, 'updateServiceGroups']);
        Route::patch('/config/servicegroup/{serviceGroup}', [ServiceGroupController::class, 'patchServiceGroup']);
        Route::delete('/config/servicegroup/{id}', [ServiceGroupController::class, 'deleteServiceGroup']);

         //Routes for Host Groups
         Route::get('/config/hostgroup/{identifier?}', [HostGroupController::class, 'getAllHostGroups']);
         Route::post('/config/hostgroup', [HostGroupController::class, 'createHostGroups']);
         Route::put('/config/hostgroup/{id}', [HostGroupController::class, 'updateHostGroups']);
         Route::patch('/config/hostgroup/{hostgroup}', [HostGroupController::class, 'patchHostGroup']);

         Route::delete('/config/hostgroup/{id}', [HostGroupController::class, 'deleteHostGroup']);


        // routes for contacts
        Route::get('/config/contact/{identifier?}', [ContactController::class, 'getAllContacts']);
        Route::post('/config/contact', [ContactController::class, 'createContact']);

        Route::put('/config/contact/{id}', [ContactController::class, 'updateContact']);
        Route::patch('/config/contact/{id}', [ContactController::class, 'patchContact']);
        Route::delete('/config/contact/{id}', [ContactController::class, 'deleteContact']);


         // routes for command
         Route::get('/config/command/{identifier?}', [CommandController::class, 'getAllCommands']);
         Route::post('/config/command', [CommandController::class, 'createCommand']);

         Route::put('/config/command/{id}', [CommandController::class, 'updateCommand']);

         Route::patch('/config/command/{command}', [CommandController::class, 'patchCommand']);

         Route::delete('/config/command/{id}', [CommandController::class, 'deleteCommand']);

         // status routes
         Route::get('status/{type}/{name?}', [StatusController::class, 'getStatus']);


    });


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
