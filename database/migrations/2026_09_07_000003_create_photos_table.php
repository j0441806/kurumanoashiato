<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('drive_record_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('file_path', 255);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE photos ADD CONSTRAINT photos_exactly_one_attachment_check CHECK (
            (car_id IS NOT NULL AND drive_record_id IS NULL)
            OR (car_id IS NULL AND drive_record_id IS NOT NULL)
        )');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
