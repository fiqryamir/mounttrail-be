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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            $table->integer('experience_years')->nullable();
            $table->json('certifications')->nullable();
            $table->json('specialties')->nullable();
            $table->decimal('rating', 3, 2)->nullable()->default(0.00);
            $table->boolean('is_available')->nullable()->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'bio',
                'experience_years',
                'certifications',
                'specialties',
                'rating',
                'is_available'
            ]);
        });
    }
};
