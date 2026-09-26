<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

// Used on deploy (docker/start.sh): fill a fresh database with the Homely demo content
// exactly once — redeploys must not duplicate agents and listings.
Artisan::command('demo:seed-if-empty', function () {
    if (User::query()->exists()) {
        $this->info('Database already has users — demo seed skipped.');

        return;
    }

    $this->call('db:seed', ['--force' => true]);
})->purpose('Seed the demo data into an empty database');
