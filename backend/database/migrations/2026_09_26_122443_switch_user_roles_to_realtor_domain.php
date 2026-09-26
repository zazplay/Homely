<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The project moved from a meal-subscription domain to real estate:
 * customer -> client, kitchen -> realtor.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'customer')->update(['role' => 'client']);
        DB::table('users')->where('role', 'kitchen')->update(['role' => 'realtor']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('client')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'client')->update(['role' => 'customer']);
        DB::table('users')->where('role', 'realtor')->update(['role' => 'kitchen']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('customer')->change();
        });
    }
};
