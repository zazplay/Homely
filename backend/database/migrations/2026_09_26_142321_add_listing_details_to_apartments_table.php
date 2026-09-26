<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->string('property_type', 20)->default('apartment')->index()->after('deal_type');
            $table->unsignedTinyInteger('bathrooms')->nullable()->after('rooms');
            $table->string('badge', 20)->nullable()->after('is_published');   // new / hot / price_drop
            $table->boolean('is_new_build')->default(false)->index()->after('badge');
        });
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn(['property_type', 'bathrooms', 'badge', 'is_new_build']);
        });
    }
};
