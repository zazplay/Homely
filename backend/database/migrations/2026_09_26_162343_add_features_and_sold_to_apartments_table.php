<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->json('features')->nullable()->after('is_new_build');   // list of App\Enums\Feature values
            $table->unsignedSmallInteger('year_built')->nullable()->after('total_floors');
            // Sold listings leave the catalog but stay on the realtor's profile ("Sold" tab).
            $table->boolean('is_sold')->default(false)->index()->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn(['features', 'year_built', 'is_sold']);
        });
    }
};
