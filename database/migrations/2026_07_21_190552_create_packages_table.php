<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['regular', 'private']);
            $table->decimal('price', 12, 2);
            $table->integer('duration_months')->default(1);
            $table->integer('sessions_count')->nullable()->comment('Untuk private per sesi');
            $table->integer('duration_minutes')->default(90)->comment('Durasi per pertemuan');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
