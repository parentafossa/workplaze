<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GetEAController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Login pass email and password
Route::post('/login', [AuthController::class, 'login']);

//validate current token
Route::middleware('auth:sanctum')->get('validate', [AuthController::class, 'validateToken']);

//get current user info
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Driver Log
Route::middleware(['auth:sanctum'])->group(function () {
    //get list of driver activities
    Route::get('getdriveractivity', [GetEAController::class, 'getDriverActivity']);

    //get list of trucks
    Route::get('gettrucks', [GetEAController::class, 'getTruckList']);

    //get list of active drivers
    Route::get('getdrivers', [GetEAController::class, 'getDriverList']);

    //get last status of a driver, pass emp_id and truck_no
    Route::get('getlaststatus', [GetEAController::class, 'getLastStatus']);
    Route::get('getdrivertrip', [GetEAController::class, 'getDriverTrip']);

    //log driver status, pass:
    /*  'emp_id' => 'required|string'
    'driveraction_id' => 'required|integer'
    'driveraction_type' => 'required|string'
    'driver_timestamp' => 'required|date_format:Y-m-d H:i:s', // Expecting a date in this format
    'truck_no' => 'required|string|max:20'
    'device_info' => 'nullable|string'
    'latitude' => 'nullable|numeric'
    'longitude' => 'nullable|numeric'
    'accuracy' => 'nullable|numeric'
    'altitude' => 'nullable|numeric'
    'speed' => 'nullable|numeric'
    'address' => 'nullable|string'
    'remark' => 'nullable|string' */

    Route::post('/logdriver', [GetEAController::class, 'LogDriver']);

    Route::post('/logout', [AuthController::class, 'logout']);

    /*     
    'caah_adv_req_id' => 'required',
    'usage_datetime' => 'required|date',
    'amount' => 'required|numeric|min:0',
    'purpose_id' => 'required|exists:ca_purposes,id',
    'remarks' => 'nullable|string',
    'files.*' => 'file|mimes:jpeg,png,pdf|max:5120', // Optional files
    */

    Route::post('/cashusage', [GetEAController::class, 'cashUsage']);

});

Route::post('/tokens/create', function (Request $request) {
    $token = $request->user()->createToken($request->token_name);
    //Log::info($request->user());

    return ['token' => $token->plainTextToken];
});
