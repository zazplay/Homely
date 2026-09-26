<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartment_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            // Only the file path is stored; the image itself lives on disk (storage/app/public).
            $table->string('path');
            $table->string('mime_type', 50);
            $table->unsignedInteger('size_bytes');
            $table->unsignedSmallInteger('position')->default(0);  // order in the gallery
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartment_photos');
    }
};
