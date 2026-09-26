<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public profile of a realtor (1:1 with users). Kept out of `users`,
 * which stays about authentication only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();                  // "Senior agent"
            $table->string('agency')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('bio')->nullable();
            $table->string('license_number', 30)->nullable();
            $table->boolean('is_verified')->default(false)->index();   // license checked by the platform
            $table->unsignedTinyInteger('experience_years')->default(0);
            // Aggregates shown on the profile (fed by the deals / reviews systems).
            $table->decimal('rating', 2, 1)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('deals_count')->default(0);
            $table->unsignedBigInteger('sales_volume_cents')->default(0);
            // Lists of enum values (see App\Enums\Specialization, ServiceArea, Language).
            $table->json('specializations');
            $table->json('areas');
            $table->json('languages');
            $table->string('cover_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_profiles');
    }
};
