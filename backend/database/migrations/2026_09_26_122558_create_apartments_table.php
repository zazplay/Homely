<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('realtor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('deal_type', 10)->index();           // sale / rent
            // Money as integer minor units (cents) — never float. Rent: price per month.
            $table->unsignedBigInteger('price_cents')->index();
            $table->string('city', 100)->index();
            $table->string('address');
            $table->unsignedTinyInteger('rooms')->index();
            $table->decimal('area', 8, 2);                      // square meters, e.g. 54.30
            $table->unsignedSmallInteger('floor')->nullable();
            $table->unsignedSmallInteger('total_floors')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
