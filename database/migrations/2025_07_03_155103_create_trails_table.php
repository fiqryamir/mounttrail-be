<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mount_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description');
            $table->enum('difficulty_level', ['easy', 'moderate', 'hard', 'extreme']);
            $table->decimal('distance_km', 8, 2);
            $table->integer('estimated_hours');
            $table->json('waypoints')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trails');
    }
};
