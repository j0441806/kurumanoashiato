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
        Schema::create('drive_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->restrictOnDelete();
            $table->date('drive_date');
            $table->string('departure', 100);
            $table->string('destination', 100);
            $table->string('spots', 255)->nullable();
            $table->json('road_types')->nullable();
            $table->decimal('distance', 7, 1);
            $table->text('note_theme')->nullable();
            $table->text('note_good_points')->nullable();
            $table->text('note_issues')->nullable();
            $table->text('note_next_action')->nullable();
            $table->text('note_free')->nullable();
            $table->timestamps();

            $table->index('drive_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drive_records');
    }
};
