<?php
 
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-oauth', function () {
    return response()->json([
        'clients' => DB::table('oauth_clients')->get()->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'secret_is_null' => is_null($client->secret),
                'secret_is_empty' => empty($client->secret),
                'secret_length' => strlen($client->secret ?? ''),
                'grant_types' => $client->grant_types,
            ];
        })
    ]);
});
