<?php

// Makes sure a Passport password-grant client exists, so login works on a
// fresh database. Mirrors DatabaseSeeder: the secret is nulled so the client
// is treated as public (see server_passport_setup.md, option A).

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! Schema::hasTable('oauth_clients')) {
    echo "oauth_clients table not found, skipping Passport client setup.\n";
    exit(0);
}

$passwordClients = fn () => DB::table('oauth_clients')->where('grant_types', 'like', '%password%');

if ($passwordClients()->exists()) {
    echo "Passport password client already exists.\n";
    exit(0);
}

Artisan::call('passport:client', [
    '--password' => true,
    '--name' => 'Quizlo Password Client',
    '--no-interaction' => true,
]);

$passwordClients()->update(['secret' => null]);

echo "Passport password client created.\n";
